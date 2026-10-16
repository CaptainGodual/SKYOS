namespace SKYOS_ACARS_Client.Models;

public static class AppConfig
{
    // Change this to your actual Laravel backend URL
    // Must use HTTPS for production deployments
    public static string BaseUrl { get; set; } = "https://127.0.0.1:8000";

    public static string ApiBaseUrl => $"{BaseUrl}/api/acars";

    public static string LoginUrl => $"{ApiBaseUrl}/login";
    public static string FlightsUrl => $"{ApiBaseUrl}/flights";
    public static string FlightEventUrl(int flightId) => $"{ApiBaseUrl}/flights/{flightId}/event";
    public static string PirepsUrl => $"{ApiBaseUrl}/pireps";

    public static int LoginRetryDelaySeconds => 60;
    public static int MaxLoginRetries => 3;
}
