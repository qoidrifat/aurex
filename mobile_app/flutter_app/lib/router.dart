import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'models/analysis_model.dart';
import 'screens/splash_screen.dart';
import 'screens/onboarding_screen.dart';
import 'screens/login_screen.dart';
import 'screens/register_screen.dart';
import 'screens/forgot_password_screen.dart';
import 'screens/upload_screen.dart';
import 'screens/analysis_screen.dart';
import 'screens/result_screen.dart';
import 'screens/profile_screen.dart';
import 'screens/history_screen.dart';
import 'screens/settings_screen.dart';
import 'screens/help_screen.dart';

/// Defines all route paths as constants to avoid string duplication.
class RoutePaths {
  static const splash = '/splash';
  static const onboarding = '/onboarding';
  static const login = '/login';
  static const register = '/register';
  static const forgotPassword = '/forgot-password';
  static const upload = '/upload';
  static const analyze = '/analyze/:imageId';
  static const result = '/result';
  static const profile = '/profile';
  static const history = '/history';
  static const settings = '/settings';
  static const help = '/help';
}

/// Builds the GoRouter configuration for AUREX.
GoRouter buildRouter() {
  return GoRouter(
    initialLocation: RoutePaths.splash,
    routes: [
      GoRoute(
        path: RoutePaths.splash,
        name: 'splash',
        builder: (context, state) => const SplashScreen(),
      ),
      GoRoute(
        path: RoutePaths.onboarding,
        name: 'onboarding',
        builder: (context, state) => const OnboardingScreen(),
      ),
      GoRoute(
        path: RoutePaths.login,
        name: 'login',
        builder: (context, state) => const LoginScreen(),
      ),
      GoRoute(
        path: RoutePaths.register,
        name: 'register',
        builder: (context, state) => const RegisterScreen(),
      ),
      GoRoute(
        path: RoutePaths.forgotPassword,
        name: 'forgot-password',
        builder: (context, state) => const ForgotPasswordScreen(),
      ),
      GoRoute(
        path: RoutePaths.upload,
        name: 'upload',
        builder: (context, state) => const UploadScreen(),
      ),
      GoRoute(
        path: RoutePaths.analyze,
        name: 'analyze',
        builder: (context, state) {
          final imageId = int.parse(state.pathParameters['imageId']!);
          return AnalysisScreen(imageId: imageId);
        },
      ),
      GoRoute(
        path: RoutePaths.result,
        name: 'result',
        builder: (context, state) {
          final analysis = state.extra as AnalysisModel;
          return ResultScreen(analysis: analysis!);
        },
      ),
      GoRoute(
        path: RoutePaths.profile,
        name: 'profile',
        builder: (context, state) => const ProfileScreen(),
      ),
      GoRoute(
        path: RoutePaths.history,
        name: 'history',
        builder: (context, state) => const HistoryScreen(),
      ),
      GoRoute(
        path: RoutePaths.settings,
        name: 'settings',
        builder: (context, state) => const SettingsScreen(),
      ),
      GoRoute(
        path: RoutePaths.help,
        name: 'help',
        builder: (context, state) => const HelpScreen(),
      ),
    ],
  );
}
