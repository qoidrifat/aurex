import 'package:flutter/material.dart';
import '../core/theme/colors.dart';
import '../core/theme/typography.dart';
import '../core/theme/spacing.dart';

/// Card untuk menampilkan hasil analisis gaya dengan micro-interaction.
class StyleCard extends StatefulWidget {
  final String title;
  final String value;
  final IconData icon;
  final Color? accentColor;

  const StyleCard({
    super.key,
    required this.title,
    required this.value,
    required this.icon,
    this.accentColor,
  });

  @override
  State<StyleCard> createState() => _StyleCardState();
}

class _StyleCardState extends State<StyleCard> {
  bool _isPressed = false;

  @override
  Widget build(BuildContext context) {
    final accent = widget.accentColor ?? AurexColors.olive;
    final disableAnimations = MediaQuery.maybeOf(context)?.disableAnimations ?? false;

    return Semantics(
      label: '${widget.title}: ${widget.value}',
      child: GestureDetector(
        onTapDown: disableAnimations ? null : (_) => setState(() => _isPressed = true),
        onTapUp: disableAnimations ? null : (_) => setState(() => _isPressed = false),
        onTapCancel: () => setState(() => _isPressed = false),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 150),
          curve: Curves.easeOutCubic,
          margin: AppSpacing.marginCard,
          padding: AppSpacing.paddingCard,
          decoration: BoxDecoration(
            color: _isPressed
                ? accent.withValues(alpha: 0.15)
                : AurexColors.charcoal.withValues(alpha: 0.5),
            borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
            border: Border.all(
              color: _isPressed
                  ? accent.withValues(alpha: 0.6)
                  : accent.withValues(alpha: 0.2),
              width: _isPressed ? 1.5 : 1,
            ),
            boxShadow: _isPressed
                ? [
                    BoxShadow(
                      color: accent.withValues(alpha: 0.1),
                      blurRadius: 8,
                      offset: const Offset(0, 2),
                    ),
                  ]
                : [],
          ),
          transform: _isPressed ? Matrix4.translationValues(0, 1, 0) : Matrix4.identity(),
          child: Row(
            children: [
              Semantics(
                label: 'Ikon ${widget.title}',
                excludeSemantics: true,
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 200),
                  padding: const EdgeInsets.all(AppSpacing.sm),
                  decoration: BoxDecoration(
                    color: _isPressed
                        ? accent.withValues(alpha: 0.15)
                        : Colors.transparent,
                    borderRadius: BorderRadius.circular(AppSpacing.radiusSm),
                  ),
                  child: Icon(widget.icon, color: accent, size: AppSpacing.iconLg),
                ),
              ),
              const SizedBox(width: 20),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(widget.title, style: AurexTypography.bodyMedium),
                    const SizedBox(height: AppSpacing.xs),
                    Text(
                      widget.value,
                      style: AurexTypography.bodyLarge.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
