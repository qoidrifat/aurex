import 'package:flutter/material.dart';

/// Custom page transition builder untuk AUREX.
///
/// Item #1 Bulan 3 — Motion Design:
/// - Fade + Scale transition untuk pengalaman navigasi yang mulus
/// - Slide transition dari kanan untuk halaman detail
/// - Mendukung reduce motion (MediaQuery.disableAnimations)
class PageTransitions {
  /// Fade + subtle scale transition (untuk halaman utama)
  static Widget fadeThrough(BuildContext context, Animation<double> animation,
      Animation<double> secondaryAnimation, Widget child) {
    final disableAnimations = MediaQuery.maybeOf(context)?.disableAnimations ?? false;

    if (disableAnimations) return child;

    return FadeTransition(
      opacity: animation,
      child: ScaleTransition(
        scale: Tween<double>(begin: 0.97, end: 1.0).animate(
          CurvedAnimation(parent: animation, curve: Curves.easeOutCubic),
        ),
        child: child,
      ),
    );
  }

  /// Slide from right (untuk halaman detail/profile)
  static Widget slideFromRight(BuildContext context, Animation<double> animation,
      Animation<double> secondaryAnimation, Widget child) {
    final disableAnimations = MediaQuery.maybeOf(context)?.disableAnimations ?? false;

    if (disableAnimations) return child;

    return SlideTransition(
      position: Tween<Offset>(
        begin: const Offset(0.15, 0.0),
        end: Offset.zero,
      ).animate(CurvedAnimation(
        parent: animation,
        curve: Curves.easeOutCubic,
        reverseCurve: Curves.easeInCubic,
      )),
      child: FadeTransition(
        opacity: animation,
        child: child,
      ),
    );
  }

  /// Slide from bottom (untuk modal/sheets)
  static Widget slideFromBottom(BuildContext context, Animation<double> animation,
      Animation<double> secondaryAnimation, Widget child) {
    final disableAnimations = MediaQuery.maybeOf(context)?.disableAnimations ?? false;

    if (disableAnimations) return child;

    return SlideTransition(
      position: Tween<Offset>(
        begin: const Offset(0.0, 0.08),
        end: Offset.zero,
      ).animate(CurvedAnimation(
        parent: animation,
        curve: Curves.easeOutCubic,
        reverseCurve: Curves.easeInCubic,
      )),
      child: child,
    );
  }
}

/// Widget pembungkus yang memberikan fade-in animation saat pertama muncul.
class FadeInWidget extends StatefulWidget {
  final Widget child;
  final Duration delay;
  final Duration duration;

  const FadeInWidget({
    super.key,
    required this.child,
    this.delay = Duration.zero,
    this.duration = const Duration(milliseconds: 400),
  });

  @override
  State<FadeInWidget> createState() => _FadeInWidgetState();
}

class _FadeInWidgetState extends State<FadeInWidget>
    with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _fadeAnimation;
  late Animation<Offset> _slideAnimation;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: widget.duration,
    );

    _fadeAnimation = CurvedAnimation(
      parent: _controller,
      curve: Curves.easeOut,
    );

    _slideAnimation = Tween<Offset>(
      begin: const Offset(0.0, 0.04),
      end: Offset.zero,
    ).animate(CurvedAnimation(
      parent: _controller,
      curve: Curves.easeOutCubic,
    ));

    Future.delayed(widget.delay, () {
      if (mounted) _controller.forward();
    });
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final disableAnimations = MediaQuery.maybeOf(context)?.disableAnimations ?? false;

    if (disableAnimations) return widget.child;

    return FadeTransition(
      opacity: _fadeAnimation,
      child: SlideTransition(
        position: _slideAnimation,
        child: widget.child,
      ),
    );
  }
}

/// Staggered fade-in untuk list items (setiap item muncul bergantian)
class StaggeredFadeIn extends StatelessWidget {
  final List<Widget> children;
  final Duration itemDelay;

  const StaggeredFadeIn({
    super.key,
    required this.children,
    this.itemDelay = const Duration(milliseconds: 80),
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: List.generate(children.length, (index) {
        return FadeInWidget(
          delay: itemDelay * index,
          child: children[index],
        );
      }),
    );
  }
}

/// Staggered fade-in untuk row items (horizontal)
class StaggeredRowFadeIn extends StatelessWidget {
  final List<Widget> children;
  final Duration itemDelay;

  const StaggeredRowFadeIn({
    super.key,
    required this.children,
    this.itemDelay = const Duration(milliseconds: 60),
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: List.generate(children.length, (index) {
        return FadeInWidget(
          delay: itemDelay * index,
          child: children[index],
        );
      }),
    );
  }
}
