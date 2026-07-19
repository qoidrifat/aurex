import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../core/theme/colors.dart';
import '../core/theme/typography.dart';
import '../core/theme/spacing.dart';
import '../providers/auth_provider.dart';
import '../router.dart';
import '../widgets/primary_button.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _emailFocusNode = FocusNode();
  final _passwordFocusNode = FocusNode();
  bool _obscurePassword = true;

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    _emailFocusNode.dispose();
    _passwordFocusNode.dispose();
    super.dispose();
  }

  Future<void> _handleLogin() async {
    if (!_formKey.currentState!.validate()) return;

    final success = await ref.read(authProvider.notifier).login(
          _emailController.text.trim(),
          _passwordController.text,
        );

    if (!mounted) return;

    if (success) {
      context.goNamed('upload');
    } else {
      final errorMessage = ref.read(authProvider).errorMessage;
      if (errorMessage != null) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Row(
              children: [
                const Icon(Icons.error_outline, color: Colors.white, size: AppSpacing.iconMd),
                const SizedBox(width: AppSpacing.sm),
                Expanded(child: Text(errorMessage)),
              ],
            ),
            backgroundColor: AurexColors.error,
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
            ),
            margin: const EdgeInsets.all(AppSpacing.md),
            action: SnackBarAction(
              label: 'OK',
              textColor: Colors.white,
              onPressed: () => ref.read(authProvider.notifier).clearError(),
            ),
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProvider);

    return Scaffold(
      body: SafeArea(
        child: SingleChildScrollView(
          padding: AppSpacing.paddingScreen,
          child: Form(
            key: _formKey,
            child: FocusTraversalGroup(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const SizedBox(height: AppSpacing.xxxl),

                  // Header
                  Semantics(
                    header: true,
                    child: Column(
                      children: [
                        Container(
                          width: 80,
                          height: 80,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            color: AurexColors.olive.withValues(alpha: 0.1),
                          ),
                          child: const Icon(
                            Icons.auto_awesome,
                            size: 40,
                            color: AurexColors.olive,
                          ),
                        ),
                        const SizedBox(height: AppSpacing.lg),
                        Text(
                          'Welcome Back',
                          style: AurexTypography.heading1,
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: AppSpacing.sm),
                        Text(
                          'Sign in to continue your style journey',
                          style: AurexTypography.bodyMedium,
                          textAlign: TextAlign.center,
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: AppSpacing.xxl),

                  // Email field
                  TextFormField(
                    controller: _emailController,
                    focusNode: _emailFocusNode,
                    keyboardType: TextInputType.emailAddress,
                    textInputAction: TextInputAction.next,
                    decoration: InputDecoration(
                      labelText: 'Email',
                      hintText: 'Enter your email',
                      filled: true,
                      fillColor: AurexColors.charcoal.withValues(alpha: 0.5),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
                      ),
                      enabledBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
                        borderSide: BorderSide(
                          color: AurexColors.grey.withValues(alpha: 0.3),
                        ),
                      ),
                      focusedBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
                        borderSide: const BorderSide(
                          color: AurexColors.olive,
                          width: 2,
                        ),
                      ),
                      errorBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
                        borderSide: const BorderSide(color: AurexColors.error),
                      ),
                      contentPadding: AppSpacing.paddingFormField,
                      prefixIcon: const Icon(
                        Icons.email_outlined,
                        color: AurexColors.grey,
                      ),
                    ),
                    validator: (value) {
                      if (value == null || value.trim().isEmpty) {
                        return 'Email tidak boleh kosong';
                      }
                      if (!RegExp(r'^[^@]+@[^@]+\.[^@]+$').hasMatch(value.trim())) {
                        return 'Format email tidak valid';
                      }
                      return null;
                    },
                    onFieldSubmitted: (_) => _passwordFocusNode.requestFocus(),
                  ),

                  const SizedBox(height: AppSpacing.md),

                  // Password field
                  TextFormField(
                    controller: _passwordController,
                    focusNode: _passwordFocusNode,
                    obscureText: _obscurePassword,
                    textInputAction: TextInputAction.done,
                    onFieldSubmitted: (_) => _handleLogin(),
                    decoration: InputDecoration(
                      labelText: 'Password',
                      hintText: 'Enter your password',
                      filled: true,
                      fillColor: AurexColors.charcoal.withValues(alpha: 0.5),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
                      ),
                      enabledBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
                        borderSide: BorderSide(
                          color: AurexColors.grey.withValues(alpha: 0.3),
                        ),
                      ),
                      focusedBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
                        borderSide: const BorderSide(
                          color: AurexColors.olive,
                          width: 2,
                        ),
                      ),
                      errorBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
                        borderSide: const BorderSide(color: AurexColors.error),
                      ),
                      contentPadding: AppSpacing.paddingFormField,
                      prefixIcon: const Icon(
                        Icons.lock_outlined,
                        color: AurexColors.grey,
                      ),
                      suffixIcon: Semantics(
                        label: _obscurePassword ? 'Tampilkan password' : 'Sembunyikan password',
                        button: true,
                        child: IconButton(
                          icon: Icon(
                            _obscurePassword ? Icons.visibility_off : Icons.visibility,
                            color: AurexColors.grey,
                          ),
                          onPressed: () {
                            setState(() => _obscurePassword = !_obscurePassword);
                          },
                        ),
                      ),
                    ),
                    validator: (value) {
                      if (value == null || value.isEmpty) {
                        return 'Password tidak boleh kosong';
                      }
                      if (value.length < 8) {
                        return 'Password minimal 8 karakter';
                      }
                      return null;
                    },
                  ),

                  const SizedBox(height: AppSpacing.sm),

                  // Forgot Password
                  Align(
                    alignment: Alignment.centerRight,
                    child: TextButton(
                      onPressed: () => context.pushNamed('forgot-password'),
                      style: TextButton.styleFrom(
                        padding: const EdgeInsets.symmetric(
                          horizontal: AppSpacing.sm,
                          vertical: AppSpacing.xs,
                        ),
                      ),
                      child: Text(
                        'Forgot Password?',
                        style: AurexTypography.bodyMedium.copyWith(
                          color: AurexColors.olive,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  ),

                  const SizedBox(height: AppSpacing.md),

                  // Login button
                  PrimaryButton(
                    text: 'Login',
                    isLoading: authState.isLoading,
                    onPressed: _handleLogin,
                    icon: Icons.login,
                  ),

                  const SizedBox(height: AppSpacing.lg),

                  // Divider
                  Row(
                    children: [
                      const Expanded(child: Divider(color: AurexColors.greyLight)),
                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md),
                        child: Text(
                          'or',
                          style: AurexTypography.bodyMedium.copyWith(
                            color: AurexColors.grey,
                          ),
                        ),
                      ),
                      const Expanded(child: Divider(color: AurexColors.greyLight)),
                    ],
                  ),

                  const SizedBox(height: AppSpacing.lg),

                  // Register link
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        "Don't have an account? ",
                        style: AurexTypography.bodyMedium,
                      ),
                      GestureDetector(
                        onTap: () => context.pushNamed('register'),
                        child: Text(
                          'Register',
                          style: AurexTypography.bodyLarge.copyWith(
                            color: AurexColors.olive,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
