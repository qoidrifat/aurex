import 'package:flutter/material.dart';
import '../core/theme/colors.dart';
import '../core/theme/typography.dart';
import '../core/theme/spacing.dart';

/// Primary button dengan micro-interaction (scale animation saat ditekan).
///
/// Menggunakan [Listener] untuk scale effect agar [ElevatedButton] tetap
/// mempertahankan semantic role, keyboard accessibility, dan ripple effect.
class PrimaryButton extends StatefulWidget {
  final String text;
  final VoidCallback onPressed;
  final Color color;
  final bool isLoading;
  final IconData? icon;

  const PrimaryButton({
    super.key,
    required this.text,
    required this.onPressed,
    this.color = AurexColors.olive,
    this.isLoading = false,
    this.icon,
  });

  @override
  State<PrimaryButton> createState() => _PrimaryButtonState();
}

class _PrimaryButtonState extends State<PrimaryButton>
    with SingleTickerProviderStateMixin {
  late AnimationController _scaleController;
  late Animation<double> _scaleAnimation;

  @override
  void initState() {
    super.initState();
    _scaleController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 100),
      lowerBound: 0.97,
      upperBound: 1.0,
    );
    _scaleAnimation = CurvedAnimation(
      parent: _scaleController,
      curve: Curves.easeOutCubic,
    );
  }

  @override
  void dispose() {
    _scaleController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final disableAnimations = MediaQuery.maybeOf(context)?.disableAnimations ?? false;

    final Widget button;

    if (widget.isLoading) {
      button = Semantics(
        liveRegion: true,
        label: 'Memuat...',
        child: ElevatedButton(
          onPressed: null,
          style: ElevatedButton.styleFrom(
            backgroundColor: widget.color.withValues(alpha: 0.6),
            disabledBackgroundColor: widget.color.withValues(alpha: 0.6),
            padding: AppSpacing.paddingButton,
            minimumSize: const Size.fromHeight(AppSpacing.buttonHeight),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
            ),
            elevation: 0,
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
    } else {
      button = ElevatedButton(
        onPressed: widget.onPressed,
        style: ElevatedButton.styleFrom(
          backgroundColor: widget.color,
          padding: AppSpacing.paddingButton,
          minimumSize: const Size.fromHeight(AppSpacing.buttonHeight),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
          ),
          elevation: 0,
        ),
        child: widget.icon != null
            ? Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(widget.icon, size: AppSpacing.iconMd),
                  const SizedBox(width: AppSpacing.sm),
                  Text(widget.text, style: AurexTypography.buttonText),
                ],
              )
            : Text(widget.text, style: AurexTypography.buttonText),
      );
    }

    if (disableAnimations || widget.isLoading) return button;

    // Gunakan Listener untuk scale effect tanpa mengganggu Button semantics
    return Listener(
      onPointerDown: (_) => _scaleController.forward(),
      onPointerUp: (_) => _scaleController.reverse(),
      onPointerCancel: (_) => _scaleController.reverse(),
      child: AnimatedBuilder(
        animation: _scaleAnimation,
        builder: (context, child) => Transform.scale(
          scale: _scaleAnimation.value,
          child: child,
        ),
        child: button,
      ),
    );
  }
}
