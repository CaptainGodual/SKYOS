using SKYOS_ACARS_Client.Services;
using SKYOS_ACARS_Client.Views;
using System.Windows;

namespace SKYOS_ACARS_Client;

public partial class App : Application
{
    protected override void OnStartup(StartupEventArgs e)
    {
        base.OnStartup(e);

        var apiService = new AcarsApiService();
        var loginWindow = new Views.LoginWindow(apiService);
        loginWindow.Show();
    }
}
