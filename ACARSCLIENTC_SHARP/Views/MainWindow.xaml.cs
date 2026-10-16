using SKYOS_ACARS_Client.Models;
using SKYOS_ACARS_Client.Services;
using System.Windows;
using System.Windows.Controls;

namespace SKYOS_ACARS_Client.Views;

public partial class MainWindow : Window
{
    private readonly AcarsApiService _apiService;
    private List<FlightDto> _flights = new();

    public MainWindow(AcarsApiService apiService)
    {
        InitializeComponent();
        _apiService = apiService;
        _apiService.OnError += OnError;
        _apiService.OnTokenExpired += OnTokenExpired;

        Loaded += async (s, e) => await LoadFlightsAsync();
    }

    private async Task LoadFlightsAsync()
    {
        try
        {
            var flights = await _apiService.GetFlightsAsync();
            if (flights != null)
            {
                _flights = flights;
                FlightsDataGrid.ItemsSource = _flights;
            }
        }
        catch (Exception ex)
        {
            MessageBox.Show($"Error loading flights: {ex.Message}", "Error", 
                MessageBoxButton.OK, MessageBoxImage.Error);
        }
    }

    private async void RefreshButton_Click(object sender, RoutedEventArgs e)
    {
        RefreshButton.IsEnabled = false;
        try
        {
            await LoadFlightsAsync();
        }
        finally
        {
            RefreshButton.IsEnabled = true;
        }
    }

    private async void TakeoffButton_Click(object sender, RoutedEventArgs e)
    {
        if (FlightsDataGrid.SelectedItem is not FlightDto flight) return;

        var success = await _apiService.ReportFlightEventAsync(
            flight.Id, "Flight took off",
            int.TryParse(AltitudeTextBox.Text, out var alt) ? alt : null,
            int.TryParse(GroundSpeedTextBox.Text, out var gs) ? gs : null);

        if (success)
        {
            MessageBox.Show("Takeoff event reported.", "Success", MessageBoxButton.OK, MessageBoxImage.Information);
            await LoadFlightsAsync();
        }
    }

    private async void LandButton_Click(object sender, RoutedEventArgs e)
    {
        if (FlightsDataGrid.SelectedItem is not FlightDto flight) return;

        var result = MessageBox.Show("Complete this flight?", "Confirm Landing", 
            MessageBoxButton.YesNo, MessageBoxImage.Question);

        if (result == MessageBoxResult.Yes)
        {
            var success = await _apiService.ReportFlightEventAsync(
                flight.Id, "Flight completed",
                int.TryParse(AltitudeTextBox.Text, out var alt) ? alt : null,
                int.TryParse(GroundSpeedTextBox.Text, out var gs) ? gs : null);

            if (success)
            {
                MessageBox.Show("Flight completed.", "Success", MessageBoxButton.OK, MessageBoxImage.Information);
                await LoadFlightsAsync();
            }
        }
    }

    private async void CancelButton_Click(object sender, RoutedEventArgs e)
    {
        if (FlightsDataGrid.SelectedItem is not FlightDto flight) return;

        var result = MessageBox.Show("Cancel this flight?", "Confirm Cancellation", 
            MessageBoxButton.YesNo, MessageBoxImage.Question);

        if (result == MessageBoxResult.Yes)
        {
            var success = await _apiService.ReportFlightEventAsync(flight.Id, "Flight canceled");

            if (success)
            {
                MessageBox.Show("Flight canceled.", "Success", MessageBoxButton.OK, MessageBoxImage.Information);
                await LoadFlightsAsync();
            }
        }
    }

    private async void FilePirepButton_Click(object sender, RoutedEventArgs e)
    {
        if (FlightsDataGrid.SelectedItem is not FlightDto flight) return;

        var pirep = new PirepRequest
        {
            FlightNumber = flight.FlightNumber,
            Departure = flight.Departure,
            Arrival = flight.Arrival,
            BlockMinutes = flight.EstimatedMinutes,
            Remarks = PirepRemarksTextBox.Text.Trim()
        };

        var success = await _apiService.SubmitPirepAsync(pirep);

        if (success)
        {
            MessageBox.Show("PIREP submitted for review.", "Success", MessageBoxButton.OK, MessageBoxImage.Information);
            PirepRemarksTextBox.Clear();
        }
    }

    private async void OnError(string message)
    {
        await Dispatcher.InvokeAsync(() =>
        {
            MessageBox.Show(message, "Error", MessageBoxButton.OK, MessageBoxImage.Error);
        });
    }

    private async void OnTokenExpired()
    {
        await Dispatcher.InvokeAsync(() =>
        {
            MessageBox.Show("Your session has expired. Please login again.", "Session Expired", 
                MessageBoxButton.OK, MessageBoxImage.Warning);
            
            var loginWindow = new LoginWindow(_apiService);
            loginWindow.Show();
            Close();
        });
    }

    private void FlightsDataGrid_SelectionChanged(object sender, System.Windows.Controls.SelectionChangedEventArgs e)
    {
        FlightActionsPanel.IsEnabled = FlightsDataGrid.SelectedItem != null;
    }

    private void LogoutButton_Click(object sender, RoutedEventArgs e)
    {
        _apiService.Logout();
        var loginWindow = new LoginWindow(_apiService);
        loginWindow.Show();
        Close();
    }
}
