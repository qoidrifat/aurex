import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Provider untuk dark mode state.
/// Bisa di-watch oleh widget manapun untuk rebuild otomatis saat toggle.
final isDarkModeProvider = StateNotifierProvider<DarkModeNotifier, bool>((ref) {
  return DarkModeNotifier();
});

class DarkModeNotifier extends StateNotifier<bool> {
  DarkModeNotifier() : super(true) {
    _loadPreference();
  }

  static const _prefKey = 'dark_mode';

  /// Load preference dari SharedPreferences saat pertama kali
  Future<void> _loadPreference() async {
    final prefs = await SharedPreferences.getInstance();
    final isDark = prefs.getBool(_prefKey) ?? true;
    if (isDark != state) {
      state = isDark;
    }
  }

  /// Toggle dark/light mode dan simpan preferensi
  Future<void> toggle() async {
    final newValue = !state;
    state = newValue;

    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(_prefKey, newValue);
  }

  /// Set mode secara langsung
  Future<void> setDarkMode(bool value) async {
    if (value != state) {
      state = value;
      final prefs = await SharedPreferences.getInstance();
      await prefs.setBool(_prefKey, value);
    }
  }
}
