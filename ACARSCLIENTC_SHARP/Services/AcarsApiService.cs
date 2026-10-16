using System.Net;
using System.Net.Http;
using System.Text;
using System.Text.Json;
using SKYOS_ACARS_Client.Models;

namespace SKYOS_ACARS_Client.Services;

public class AcarsApiService : IDisposable
{
    private readonly HttpClient _httpClient;
    private readonly JsonSerializerOptions _jsonOptions;
    private string? _accessToken;
    private DateTime? _tokenExpiresAt;
    private int _loginAttempts = 0;

    public event Action<string>? OnError;
    public event Action<PilotInfo>? OnAuthenticated;
    public event Action? OnTokenExpired;

    public string? CurrentToken => _accessToken;
    public bool IsAuthenticated => !string.IsNullOrEmpty(_accessToken);

    public AcarsApiService()
    {
        _httpClient = new HttpClient
        {
            BaseAddress = new Uri(AppConfig.BaseUrl),
            Timeout = TimeSpan.FromSeconds(30)
        };

        // Enforce TLS 1.2 or higher for secure communication
        System.Net.ServicePointManager.SecurityProtocol =
            System.Net.SecurityProtocolType.Tls12 |
            System.Net.SecurityProtocolType.Tls13;

        _jsonOptions = new JsonSerializerOptions
        {
            PropertyNameCaseInsensitive = true,
            PropertyNamingPolicy = JsonNamingPolicy.CamelCase
        };
    }

    public async Task<bool> LoginAsync(string email, string password, CancellationToken cancellationToken = default)
    {
        if (_loginAttempts >= AppConfig.MaxLoginRetries)
        {
            OnError?.Invoke($"Too many login attempts. Wait {AppConfig.LoginRetryDelaySeconds} seconds.");
            return false;
        }

        try
        {
            var request = new LoginRequest { Email = email, Password = password };
            var json = JsonSerializer.Serialize(request, _jsonOptions);
            var content = new StringContent(json, Encoding.UTF8, "application/json");

            var response = await _httpClient.PostAsync(AppConfig.LoginUrl, content, cancellationToken);

            if (response.StatusCode == HttpStatusCode.TooManyRequests)
            {
                _loginAttempts++;
                var retryAfter = response.Headers.RetryAfter?.Delta?.Seconds ?? AppConfig.LoginRetryDelaySeconds;
                OnError?.Invoke($"Too many login attempts. Try again in {retryAfter} seconds.");
                return false;
            }

            if (!response.IsSuccessStatusCode)
            {
                _loginAttempts++;
                var errorBody = await response.Content.ReadAsStringAsync(cancellationToken);
                var error = JsonSerializer.Deserialize<ApiErrorResponse>(errorBody, _jsonOptions);
                OnError?.Invoke(error?.Message ?? "Invalid email or password.");
                return false;
            }

            _loginAttempts = 0;
            var responseJson = await response.Content.ReadAsStringAsync(cancellationToken);
            var loginResponse = JsonSerializer.Deserialize<LoginResponse>(responseJson, _jsonOptions);

            if (loginResponse == null || string.IsNullOrEmpty(loginResponse.Token))
            {
                OnError?.Invoke("Invalid server response.");
                return false;
            }

            _accessToken = loginResponse.Token;
            _tokenExpiresAt = DateTime.Parse(loginResponse.ExpiresAt);

            _httpClient.DefaultRequestHeaders.Authorization =
                new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", _accessToken);

            OnAuthenticated?.Invoke(loginResponse.Pilot);
            return true;
        }
        catch (HttpRequestException ex)
        {
            OnError?.Invoke($"Connection error: {ex.Message}");
            return false;
        }
        catch (Exception ex)
        {
            OnError?.Invoke($"Error: {ex.Message}");
            return false;
        }
    }

    public async Task<List<FlightDto>?> GetFlightsAsync(CancellationToken cancellationToken = default)
    {
        return await GetAsync<List<FlightDto>>(AppConfig.FlightsUrl, cancellationToken);
    }

    public async Task<bool> ReportFlightEventAsync(int flightId, string eventType, int? altitude = null, int? groundSpeed = null, CancellationToken cancellationToken = default)
    {
        var request = new FlightEventRequest
        {
            EventType = eventType,
            Altitude = altitude,
            GroundSpeed = groundSpeed
        };

        return await PostAsync($"{AppConfig.FlightEventUrl(flightId)}", request, cancellationToken);
    }

    public async Task<bool> SubmitPirepAsync(PirepRequest pirep, CancellationToken cancellationToken = default)
    {
        return await PostAsync(AppConfig.PirepsUrl, pirep, cancellationToken);
    }

    public async Task<bool> RefreshTokenAsync(CancellationToken cancellationToken = default)
    {
        OnTokenExpired?.Invoke();
        return false;
    }

    public async Task<bool> IsTokenValidAsync(CancellationToken cancellationToken = default)
    {
        if (string.IsNullOrEmpty(_accessToken) || _tokenExpiresAt == null)
            return false;

        if (_tokenExpiresAt.Value <= DateTime.UtcNow.AddMinutes(5))
        {
            OnTokenExpired?.Invoke();
            return false;
        }

        try
        {
            var response = await _httpClient.GetAsync(AppConfig.FlightsUrl, cancellationToken);
            return response.IsSuccessStatusCode;
        }
        catch
        {
            return false;
        }
    }

    private async Task<T?> GetAsync<T>(string url, CancellationToken cancellationToken = default)
    {
        await EnsureAuthenticatedAsync(cancellationToken);

        var response = await _httpClient.GetAsync(url, cancellationToken);

        if (response.StatusCode == HttpStatusCode.Unauthorized)
        {
            OnTokenExpired?.Invoke();
            return default;
        }

        if (!response.IsSuccessStatusCode)
        {
            var errorBody = await response.Content.ReadAsStringAsync(cancellationToken);
            OnError?.Invoke($"Server error: {response.StatusCode} - {errorBody}");
            return default;
        }

        var json = await response.Content.ReadAsStringAsync(cancellationToken);
        return JsonSerializer.Deserialize<T>(json, _jsonOptions);
    }

    private async Task<bool> PostAsync<T>(string url, T body, CancellationToken cancellationToken = default)
    {
        await EnsureAuthenticatedAsync(cancellationToken);

        var json = JsonSerializer.Serialize(body, _jsonOptions);
        var content = new StringContent(json, Encoding.UTF8, "application/json");

        var response = await _httpClient.PostAsync(url, content, cancellationToken);

        if (response.StatusCode == HttpStatusCode.Unauthorized)
        {
            OnTokenExpired?.Invoke();
            return false;
        }

        if (!response.IsSuccessStatusCode)
        {
            var errorBody = await response.Content.ReadAsStringAsync(cancellationToken);
            var error = JsonSerializer.Deserialize<ApiErrorResponse>(errorBody, _jsonOptions);
            OnError?.Invoke(error?.Message ?? $"Server error: {response.StatusCode}");
            return false;
        }

        return true;
    }

    private async Task EnsureAuthenticatedAsync(CancellationToken cancellationToken = default)
    {
        if (string.IsNullOrEmpty(_accessToken))
        {
            OnTokenExpired?.Invoke();
            throw new UnauthorizedAccessException("Not authenticated. Please login first.");
        }

        if (_tokenExpiresAt.HasValue && _tokenExpiresAt.Value <= DateTime.UtcNow.AddMinutes(2))
        {
            OnTokenExpired?.Invoke();
            throw new UnauthorizedAccessException("Token expired or expiring soon. Please login again.");
        }
    }

    public void Logout()
    {
        _accessToken = null;
        _tokenExpiresAt = null;
        _loginAttempts = 0;
        _httpClient.DefaultRequestHeaders.Authorization = null;
    }

    public void Dispose()
    {
        _httpClient?.Dispose();
    }
}
