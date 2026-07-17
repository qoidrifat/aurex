import 'package:flutter/material.dart';
import '../core/theme/colors.dart';
import '../core/theme/typography.dart';

class PrimaryButton extends StatelessWidget {
  final String text;
  final VoidCallback onPressed;
  final Color color;
  final bool isLoading;

  const PrimaryButton({
    super.key,
    required this.text,
    required this.onPressed,
    this.color = AurexColors.olive,
    this.isLoading = false,
  });

  @override
  Widget build(BuildContext context) {
    // ElevatedButton sudah memiliki semantic bawaan Flutter (button role + label dari child Text).
    // Semantics hanya perlu untuk loading state (liveRegion) agar screen reader mengumumkan perubahan.
    if (isLoading) {
      return Semantics(
        liveRegion: true,
        label: 'Memuat...',
        child: ElevatedButton(
          onPressed: null,
          style: ElevatedButton.styleFrom(
            backgroundColor: color.withValues(alpha: 0.6),
            disabledBackgroundColor: color.withValues(alpha: 0.6),
            padding: const EdgeInsets.symmetric(vertical: 16),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
            ),
          ),
          child: const SizedBox(
            height: 20,
            width: 20,
            child: CircularProgressIndicator(
              strokeWidth: 2,
              valueColor: AlwaysStoppedAnimation<Color>(AurexColors.white),
            ),
          ),
        ),
      );
    }

    return ElevatedButton(
      onPressed: onPressed,
      style: ElevatedButton.styleFrom(
        backgroundColor: color,
        padding: const EdgeInsets.symmetric(vertical: 16),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
        ),
      ),
      child: Text(
        text,
        style: AurexTypography.buttonText,
      ),
    );
  }
}
