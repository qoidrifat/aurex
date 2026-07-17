import 'package:flutter/material.dart';
import '../core/theme/colors.dart';

/// Shimmer skeleton loader untuk menampilkan loading state yang smooth.
///
/// Menghormati [MediaQuery.disableAnimations] — jika animasi dimatikan
/// oleh sistem (misalnya pengguna mengaktifkan "Reduce motion"),
/// skeleton akan menampilkan warna solid statis tanpa animasi.
class SkeletonLoader extends StatefulWidget {
  final double width;
  final double height;
  final double borderRadius;
  final EdgeInsets? margin;

  const SkeletonLoader({
    super.key,
    this.width = double.infinity,
    this.height = 20,
    this.borderRadius = 8,
    this.margin,
  });

  @override
  State<SkeletonLoader> createState() => _SkeletonLoaderState();
}

class _SkeletonLoaderState extends State<SkeletonLoader>
    with SingleTickerProviderStateMixin {
  AnimationController? _controller;
  Animation<double>? _animation;
  bool _animationsDisabled = false;

  @override
  void initState() {
    super.initState();
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _setupAnimation();
  }

  void _setupAnimation() {
    final disable = MediaQuery.of(context).disableAnimations;
    if (disable == _animationsDisabled) return; // sudah sinkron

    _animationsDisabled = disable;

    if (_animationsDisabled) {
      _controller?.dispose();
      _controller = null;
      _animation = null;
    } else {
      _controller = AnimationController(
        vsync: this,
        duration: const Duration(milliseconds: 1500),
      )..repeat();
      _animation = Tween<double>(begin: -2.0, end: 2.0).animate(
        CurvedAnimation(parent: _controller!, curve: Curves.easeInOutSine),
      );
    }
    setState(() {});
  }

  @override
  void dispose() {
    _controller?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (_animationsDisabled || _animation == null) {
      // Reduced motion: tampilkan warna solid statis tanpa animasi
      return Container(
        width: widget.width,
        height: widget.height,
        margin: widget.margin,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(widget.borderRadius),
          color: AurexColors.charcoal.withValues(alpha: 0.35),
        ),
      );
    }

    return AnimatedBuilder(
      animation: _animation!,
      builder: (context, child) {
        return Container(
          width: widget.width,
          height: widget.height,
          margin: widget.margin,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(widget.borderRadius),
            gradient: LinearGradient(
              begin: Alignment(_animation!.value - 1, 0),
              end: Alignment(_animation!.value, 0),
              colors: [
                AurexColors.charcoal.withValues(alpha: 0.3),
                AurexColors.charcoal.withValues(alpha: 0.5),
                AurexColors.charcoal.withValues(alpha: 0.3),
              ],
            ),
          ),
        );
      },
    );
  }
}

/// Skeleton untuk card style di result screen
class SkeletonCard extends StatelessWidget {
  const SkeletonCard({super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: AurexColors.charcoal.withValues(alpha: 0.5),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: AurexColors.olive.withValues(alpha: 0.3),
        ),
      ),
      child: Row(
        children: [
          const SkeletonLoader(
            width: 32,
            height: 32,
            borderRadius: 8,
          ),
          const SizedBox(width: 20),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const SkeletonLoader(
                  width: 120,
                  height: 14,
                ),
                const SizedBox(height: 8),
                const SkeletonLoader(
                  width: double.infinity,
                  height: 18,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

/// Skeleton untuk score circle di result screen
class SkeletonScoreCircle extends StatelessWidget {
  const SkeletonScoreCircle({super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(32),
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        border: Border.all(
          color: AurexColors.olive.withValues(alpha: 0.3),
          width: 4,
        ),
        color: AurexColors.olive.withValues(alpha: 0.05),
      ),
      child: const Column(
        children: [
          SkeletonLoader(
            width: 80,
            height: 64,
            borderRadius: 4,
          ),
          SizedBox(height: 8),
          SkeletonLoader(
            width: 100,
            height: 16,
            borderRadius: 4,
          ),
        ],
      ),
    );
  }
}

/// Full skeleton page untuk result screen
class SkeletonResultPage extends StatelessWidget {
  const SkeletonResultPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
      ),
      body: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          children: [
            const SkeletonScoreCircle(),
            const SizedBox(height: 48),
            const SkeletonLoader(
              width: 150,
              height: 24,
              margin: EdgeInsets.only(bottom: 16),
            ),
            const SkeletonCard(),
            const SkeletonCard(),
            const SizedBox(height: 16),
            const SkeletonLoader(
              width: 150,
              height: 24,
              margin: EdgeInsets.only(bottom: 16),
            ),
            const SkeletonCard(),
            const SkeletonCard(),
            const SkeletonCard(),
          ],
        ),
      ),
    );
  }
}
