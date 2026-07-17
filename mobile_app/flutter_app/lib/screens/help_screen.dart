import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../core/theme/typography.dart';
import '../widgets/empty_state.dart';

class HelpScreen extends StatelessWidget {
  const HelpScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Help & Support', style: AurexTypography.heading2),
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => context.pop(),
        ),
      ),
      body: const EmptyState(
        icon: Icons.help_outline,
        title: 'Help & Support',
        subtitle: 'Need assistance?\nFAQs, tutorials, and support contact coming soon!',
      ),
    );
  }
}
