using SKYOS_ACARS_Client.Services;
using SKYOS_ACARS_Client.Models;
using SKYOS_ACARS_Client.Views;
using System.Windows;

namespace SKYOS_ACARS_Client.Views;

public partial class LoginWindow : Window
{
    private readonly AcarsApiService _apiService;
    private MainWindow? _mainWindow;

    public LoginWindow(AcarsApiService apiService)
    {
        InitializeComponent();
        _apiService = apiService;
        _apiService.OnError += OnError;
        _apiService.OnAuthenticated += OnAuthenticated;
        _apiService.OnTokenExpired += OnTokenExpired;
    }

    private async void LoginButton_Click(object sender, RoutedEventArgs e)
    {
        LoginButton.IsEnabled = false;
        LoginButton.Content = "Logging in...";
        ErrorTextBlock.Text = string.Empty;

        try
        {
            var success = await _apiService.LoginAsync(EmailTextBox.Text.Trim(), PasswordBox.Password);
            if (success && _mainWindow == null)
            {
                _mainWindow = new MainWindow(_apiService);
                _mainWindow.Closed += (s, args) => Application.Current.Shutdown();
                _mainWindow.Show();
                Close();
            }
        }
        finally
        {
            LoginButton.IsEnabled = true;
            LoginButton.Content = "Login";
        }
    }

    private void OnError(string message)
    {
        Dispatcher.Invoke(() =>
        {
            ErrorTextBlock.Text = message;
            LoginButton.IsEnabled = true;
            LoginButton.Content = "Login";
        });
    }

    private void OnAuthenticated(PilotInfo pilot)
    {
        Dispatcher.Invoke(() =>
        {
            StatusText.Text = $"Logged in as {pilot.Name} ({pilot.PilotId})";
        });
    }

    private void OnTokenExpired()
    {
        Dispatcher.Invoke(() =>
        {
            ErrorTextBlock.Text = "Session expired. Please login again.";
            LoginButton.IsEnabled = true;
            LoginButton.Content = "Login";
        });
    }
}
