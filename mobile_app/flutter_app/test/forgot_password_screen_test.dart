import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../lib/screens/forgot_password_screen.dart';

// ==================== HELPERS ====================

Widget createForgotPasswordScreen() {
  return const ProviderScope(
    child: MaterialApp(
      home: ForgotPasswordScreen(),
    ),
  );
}

// ==================== TESTS ====================

void main() {
  setUp(() {
    SharedPreferences.setMockInitialValues({});
  });

  group('ForgotPasswordScreen - UI Elements', () {
    testWidgets('should display title', (tester) async {
      await tester.pumpWidget(createForgotPasswordScreen());

      expect(find.text('Lupa Password'), findsOneWidget);
    });

    testWidgets('should display description', (tester) async {
      await tester.pumpWidget(createForgotPasswordScreen());

      expect(
        find.text(
          'Masukkan email Anda dan kami akan mengirimkan link untuk mereset password.',
        ),
        findsOneWidget,
      );
    });

    testWidgets('should display email field', (tester) async {
      await tester.pumpWidget(createForgotPasswordScreen());

      expect(find.byType(TextFormField), findsOneWidget);
      expect(find.text('Email'), findsOneWidget);
    });

    testWidgets('should display submit button', (tester) async {
      await tester.pumpWidget(createForgotPasswordScreen());

      // Cari button berdasarkan tipe dan icon
      expect(find.byType(ElevatedButton), findsOneWidget);
    });

    testWidgets('should display back button', (tester) async {
      await tester.pumpWidget(createForgotPasswordScreen());

      expect(find.byIcon(Icons.arrow_back), findsOneWidget);
    });
  });

  group('ForgotPasswordScreen - Form Validation', () {
    testWidgets('should show error when email is empty', (tester) async {
      await tester.pumpWidget(createForgotPasswordScreen());

      // Tekan tombol submit tanpa mengisi email
      await tester.tap(find.byType(ElevatedButton));
      await tester.pumpAndSettle();

      expect(find.text('Email tidak boleh kosong'), findsOneWidget);
    });

    testWidgets('should show error for invalid email format', (tester) async {
      await tester.pumpWidget(createForgotPasswordScreen());

      // Isi email dengan format invalid
      await tester.enterText(find.byType(TextFormField), 'not-an-email');

      // Tekan submit
      await tester.tap(find.byType(ElevatedButton));
      await tester.pumpAndSettle();

      expect(find.text('Format email tidak valid'), findsOneWidget);
    });
  });
}
