using System.Text.Json.Serialization;

namespace SKYOS_ACARS_Client.Models;

public class LoginRequest
{
    [JsonPropertyName("email")]
    public string Email { get; set; } = string.Empty;

    [JsonPropertyName("password")]
    public string Password { get; set; } = string.Empty;
}

public class LoginResponse
{
    [JsonPropertyName("token")]
    public string Token { get; set; } = string.Empty;

    [JsonPropertyName("expires_at")]
    public string ExpiresAt { get; set; } = string.Empty;

    [JsonPropertyName("pilot")]
    public PilotInfo Pilot { get; set; } = new();
}

public class PilotInfo
{
    [JsonPropertyName("id")]
    public int Id { get; set; }

    [JsonPropertyName("name")]
    public string Name { get; set; } = string.Empty;

    [JsonPropertyName("email")]
    public string Email { get; set; } = string.Empty;

    [JsonPropertyName("pilot_id")]
    public string PilotId { get; set; } = string.Empty;
}

public class FlightDto
{
    [JsonPropertyName("id")]
    public int Id { get; set; }

    [JsonPropertyName("flight_number")]
    public string FlightNumber { get; set; } = string.Empty;

    [JsonPropertyName("departure")]
    public string Departure { get; set; } = string.Empty;

    [JsonPropertyName("arrival")]
    public string Arrival { get; set; } = string.Empty;

    [JsonPropertyName("aircraft")]
    public string Aircraft { get; set; } = string.Empty;

    [JsonPropertyName("scheduled_at")]
    public string ScheduledAt { get; set; } = string.Empty;

    [JsonPropertyName("estimated_minutes")]
    public int EstimatedMinutes { get; set; }

    [JsonPropertyName("payout_cents")]
    public int PayoutCents { get; set; }

    [JsonPropertyName("status")]
    public string Status { get; set; } = string.Empty;

    [JsonPropertyName("acars_status")]
    public string AcarsStatus { get; set; } = string.Empty;
}

public class FlightEventRequest
{
    [JsonPropertyName("event_type")]
    public string EventType { get; set; } = string.Empty;

    [JsonPropertyName("altitude")]
    public int? Altitude { get; set; }

    [JsonPropertyName("ground_speed")]
    public int? GroundSpeed { get; set; }
}

public class PirepRequest
{
    [JsonPropertyName("flight_number")]
    public string FlightNumber { get; set; } = string.Empty;

    [JsonPropertyName("departure")]
    public string Departure { get; set; } = string.Empty;

    [JsonPropertyName("arrival")]
    public string Arrival { get; set; } = string.Empty;

    [JsonPropertyName("block_minutes")]
    public int BlockMinutes { get; set; }

    [JsonPropertyName("landing_rate")]
    public int? LandingRate { get; set; }

    [JsonPropertyName("remarks")]
    public string Remarks { get; set; } = string.Empty;
}

public class ApiErrorResponse
{
    [JsonPropertyName("message")]
    public string Message { get; set; } = string.Empty;
}
