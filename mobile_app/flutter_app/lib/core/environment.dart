/// Environment aplikasi AUREX.
///
/// Digunakan untuk memisahkan konfigurasi antara development,
/// staging, dan production.
enum AppEnvironment {
  development,
  staging,
  production;

  /// Mendeteksi environment dari konstanta build atau env variable.
  ///
  /// Default: [development].
  factory AppEnvironment.detect() {
    // Bisa di-set via --dart-define saat build
    const env = String.fromEnvironment('AUREX_ENV', defaultValue: 'development');
    return AppEnvironment.values.firstWhere(
      (e) => e.name == env,
      orElse: () => AppEnvironment.development,
    );
  }

  /// Base URL untuk API backend berdasarkan environment.
  /// API base URL with version prefix.
  /// Semua environment kini menggunakan /api/v1/ prefix (API versioning).
  String get apiBaseUrl {
    switch (this) {
      case AppEnvironment.development:
        return 'http://localhost:8000/api/v1';
      case AppEnvironment.staging:
        return 'https://staging-api.aurex.app/api/v1';
      case AppEnvironment.production:
        return 'https://api.aurex.app/api/v1';
    }
  }

  /// Apakah debug mode aktif (menampilkan log, banner, dll.)
  bool get isDebug => this == AppEnvironment.development;
}
