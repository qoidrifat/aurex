import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/user_model.dart';
import '../services/auth_service.dart';
import '../services/api_service.dart';

/// Status autentikasi aplikasi
enum AuthStatus {
  /// Belum diinisialisasi (masih loading token)
  initializing,

  /// Sedang login/register
  loading,

  /// Berhasil login
  authenticated,

  /// Belum login
  unauthenticated,

  /// Terjadi error
  error,
}

/// State untuk autentikasi
class AuthState {
  final AuthStatus status;
  final UserModel? user;
  final String? errorMessage;

  const AuthState({
    this.status = AuthStatus.initializing,
    this.user,
    this.errorMessage,
  });

  AuthState copyWith({
    AuthStatus? status,
    UserModel? user,
    String? errorMessage,
  }) {
    return AuthState(
      status: status ?? this.status,
      user: user ?? this.user,
      errorMessage: errorMessage,
    );
  }

  bool get isLoading => status == AuthStatus.loading || status == AuthStatus.initializing;
  bool get isAuthenticated => status == AuthStatus.authenticated;
  bool get hasError => errorMessage != null;
}

/// Notifier untuk state autentikasi global
class AuthNotifier extends StateNotifier<AuthState> {
  final AuthService _authService;

  AuthNotifier(this._authService) : super(const AuthState());

  /// Inisialisasi: cek apakah ada token tersimpan
  Future<void> checkAuthStatus() async {
    state = state.copyWith(status: AuthStatus.initializing);
    final hasToken = await _authService.hasToken();
    if (hasToken) {
      // Jika ada token, anggap sudah login
      // Idealnya: fetch user data dari API untuk validasi token
      state = state.copyWith(status: AuthStatus.authenticated);
    } else {
      state = state.copyWith(status: AuthStatus.unauthenticated);
    }
  }

  /// Login dengan email dan password
  Future<bool> login(String email, String password) async {
    state = state.copyWith(status: AuthStatus.loading, errorMessage: null);
    try {
      final user = await _authService.login(email, password);
      state = AuthState(status: AuthStatus.authenticated, user: user);
      return true;
    } on ApiException catch (e) {
      state = state.copyWith(
        status: AuthStatus.error,
        errorMessage: e.message,
      );
      return false;
    } catch (e) {
      state = state.copyWith(
        status: AuthStatus.error,
        errorMessage: 'Terjadi kesalahan yang tidak terduga. Silakan coba lagi.',
      );
      return false;
    }
  }

  /// Register akun baru
  Future<bool> register(String name, String email, String password) async {
    state = state.copyWith(status: AuthStatus.loading, errorMessage: null);
    try {
      final user = await _authService.register(name, email, password);
      state = AuthState(status: AuthStatus.authenticated, user: user);
      return true;
    } on ApiException catch (e) {
      state = state.copyWith(
        status: AuthStatus.error,
        errorMessage: e.message,
      );
      return false;
    } catch (e) {
      state = state.copyWith(
        status: AuthStatus.error,
        errorMessage: 'Terjadi kesalahan yang tidak terduga. Silakan coba lagi.',
      );
      return false;
    }
  }

  /// Logout
  Future<void> logout() async {
    await _authService.logout();
    state = const AuthState(status: AuthStatus.unauthenticated);
  }

  /// Reset error state
  void clearError() {
    state = state.copyWith(
      status: state.user != null ? AuthStatus.authenticated : AuthStatus.unauthenticated,
      errorMessage: null,
    );
  }

  /// Set user (setelah login dari token)
  void setUser(UserModel user) {
    state = state.copyWith(user: user);
  }

  /// Kirim email forgot password
  Future<String?> forgotPassword(String email) async {
    try {
      final apiService = _authService.getApiService();
      final response = await apiService.forgotPassword(email);
      return response.data['message'] as String?;
    } on ApiException catch (e) {
      return e.message;
    } catch (e) {
      return 'Terjadi kesalahan. Silakan coba lagi.';
    }
  }

  /// Kirim ulang email verifikasi
  Future<String?> resendVerification(String email) async {
    try {
      final apiService = _authService.getApiService();
      final response = await apiService.resendVerification(email);
      return response.data['message'] as String?;
    } on ApiException catch (e) {
      return e.message;
    } catch (e) {
      return 'Terjadi kesalahan. Silakan coba lagi.';
    }
  }
}

/// Provider global untuk auth state
final authProvider = StateNotifierProvider<AuthNotifier, AuthState>((ref) {
  return AuthNotifier(ref.read(authServiceProvider));
});
