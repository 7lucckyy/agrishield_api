import 'dart:ui';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../app/providers.dart';
import '../../core/api/api_client.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/agri_widgets.dart';

class SplashScreen extends StatelessWidget {
  const SplashScreen({super.key});
  @override
  Widget build(BuildContext context) => Scaffold(
    backgroundColor: AgriColors.forest,
    body: Stack(
      fit: StackFit.expand,
      children: [
        Image.asset(
          'assets/images/northern-nigeria-farmers-onboarding.png',
          fit: BoxFit.cover,
          alignment: const Alignment(.35, -.2),
          semanticLabel: 'Two farmers inspecting sorghum and maize at sunrise',
          errorBuilder: (context, error, stackTrace) =>
              const _FarmImageFallback(),
        ),
        const DecoratedBox(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              colors: [Color(0x4D12382C), Color(0xED12382C)],
              stops: [.18, .78],
            ),
          ),
        ),
        SafeArea(
          child: Padding(
            padding: const EdgeInsets.all(AgriSpacing.lg),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const _SplashTopline(),
                const Spacer(),
                const _BrandLockup(light: true),
                const SizedBox(height: 16),
                Text(
                  'Practical guidance\nfor every field.',
                  style: Theme.of(context).textTheme.headlineMedium
                      ?.copyWith(color: Colors.white, height: 1.05),
                ),
                const SizedBox(height: 28),
                const Row(
                  children: [
                    SizedBox.square(
                      dimension: 20,
                      child: CircularProgressIndicator(
                        color: AgriColors.millet,
                        strokeWidth: 2.5,
                      ),
                    ),
                    SizedBox(width: 12),
                    Flexible(
                      child: Text(
                        'Opening your field workspace',
                        style: TextStyle(color: Color(0xFFE7F0E9)),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ],
    ),
  );
}

class WelcomeScreen extends StatefulWidget {
  const WelcomeScreen({super.key});
  @override
  State<WelcomeScreen> createState() => _WelcomeScreenState();
}

class _WelcomeScreenState extends State<WelcomeScreen> {
  final _pageController = PageController();
  int _page = 0;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    for (final slide in _onboardingSlides) {
      precacheImage(AssetImage(slide.asset), context);
    }
  }

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  void _goToPage(int page) => _pageController.animateToPage(
    page,
    duration: const Duration(milliseconds: 450),
    curve: Curves.easeOutCubic,
  );

  @override
  Widget build(BuildContext context) {
    final slide = _onboardingSlides[_page];
    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: SystemUiOverlayStyle.light,
      child: Scaffold(
        backgroundColor: AgriColors.forest,
        body: Stack(
          fit: StackFit.expand,
          children: [
            PageView.builder(
              controller: _pageController,
              itemCount: _onboardingSlides.length,
              onPageChanged: (page) => setState(() => _page = page),
              itemBuilder: (context, index) => _ParallaxSlideImage(
                controller: _pageController,
                index: index,
                slide: _onboardingSlides[index],
              ),
            ),
            const IgnorePointer(
              child: DecoratedBox(
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: [
                      Color(0x9912382C),
                      Color(0x0012382C),
                      Color(0x0012382C),
                      Color(0xCC12382C),
                      Color(0xFA0E2C22),
                    ],
                    stops: [0, .22, .4, .66, .9],
                  ),
                ),
              ),
            ),
            SafeArea(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(
                  AgriSpacing.lg,
                  AgriSpacing.md,
                  AgriSpacing.lg,
                  AgriSpacing.md,
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    IgnorePointer(
                      child: Row(
                        children: [
                          const Expanded(child: _BrandLockup(light: true)),
                          _PageCounter(
                            page: _page,
                            total: _onboardingSlides.length,
                          ),
                        ],
                      ),
                    ),
                    const Spacer(),
                    IgnorePointer(
                      child: AnimatedSwitcher(
                        duration: const Duration(milliseconds: 380),
                        switchInCurve: Curves.easeOutCubic,
                        switchOutCurve: Curves.easeInCubic,
                        layoutBuilder: (current, previous) => Stack(
                          alignment: Alignment.bottomLeft,
                          children: [...previous, ?current],
                        ),
                        transitionBuilder: (child, animation) => FadeTransition(
                          opacity: animation,
                          child: SlideTransition(
                            position: Tween(
                              begin: const Offset(0, .08),
                              end: Offset.zero,
                            ).animate(animation),
                            child: child,
                          ),
                        ),
                        child: _SlideCopy(key: ValueKey(_page), slide: slide),
                      ),
                    ),
                    const SizedBox(height: AgriSpacing.lg),
                    _PageIndicator(
                      page: _page,
                      total: _onboardingSlides.length,
                      onSelected: _goToPage,
                    ),
                    const SizedBox(height: AgriSpacing.lg),
                    FilledButton(
                      style: FilledButton.styleFrom(
                        minimumSize: const Size.fromHeight(56),
                        backgroundColor: AgriColors.millet,
                        foregroundColor: AgriColors.ink,
                        shape: const StadiumBorder(),
                      ),
                      onPressed: () => context.go('/register'),
                      child: const Text('Create my farmer account'),
                    ),
                    const SizedBox(height: 4),
                    TextButton(
                      style: TextButton.styleFrom(
                        minimumSize: const Size.fromHeight(48),
                        foregroundColor: Colors.white,
                      ),
                      onPressed: () => context.go('/sign-in'),
                      child: const Text('I already have an account'),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class SignInScreen extends ConsumerStatefulWidget {
  const SignInScreen({super.key});
  @override
  ConsumerState<SignInScreen> createState() => _SignInScreenState();
}

class _SignInScreenState extends ConsumerState<SignInScreen>
    with _AuthSubmission {
  final _formKey = GlobalKey<FormState>();
  final _phone = TextEditingController(text: '+234');
  final _password = TextEditingController();
  @override
  void dispose() {
    _phone.dispose();
    _password.dispose();
    super.dispose();
  }

  @override
  Set<String> get formFields => const {'phone', 'password'};

  Future<void> _submit() => submitAuth(
    _formKey,
    () => ref
        .read(authControllerProvider.notifier)
        .signIn(_phone.text, _password.text),
  );

  @override
  Widget build(BuildContext context) {
    return _AuthForm(
      heroAsset: 'assets/images/jigawa-farmer.jpeg',
      heroAlignment: const Alignment(.2, -.5),
      heroTagline: 'Your fields are waiting.',
      title: 'Welcome back',
      subtitle: 'Sign in with the phone number on your farmer account.',
      footer: _AuthSwitchLink(
        prompt: 'New to AgriShield?',
        action: 'Create an account',
        onPressed: () => context.go('/register'),
      ),
      children: [
        Form(
          key: _formKey,
          child: AutofillGroup(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                TextFormField(
                  controller: _phone,
                  keyboardType: TextInputType.phone,
                  textInputAction: TextInputAction.next,
                  autofillHints: const [AutofillHints.telephoneNumber],
                  decoration: const InputDecoration(
                    labelText: 'Phone number',
                    prefixIcon: Icon(Icons.phone_outlined),
                  ),
                  validator: (value) =>
                      serverError('phone') ?? _phoneValidator(value),
                ),
                const SizedBox(height: 14),
                _PasswordField(
                  controller: _password,
                  label: 'Password',
                  autofillHints: const [AutofillHints.password],
                  textInputAction: TextInputAction.done,
                  onFieldSubmitted: (_) => _submit(),
                  validator: (value) =>
                      serverError('password') ??
                      ((value?.length ?? 0) < 8 ? 'Enter your password' : null),
                ),
                if (formError != null) ...[
                  const SizedBox(height: AgriSpacing.md),
                  _AuthErrorBanner(message: formError!),
                ],
                const SizedBox(height: AgriSpacing.lg),
                _AuthSubmitButton(
                  label: 'Sign in',
                  loading: submitting,
                  onPressed: _submit,
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }
}

class RegisterScreen extends ConsumerStatefulWidget {
  const RegisterScreen({super.key});
  @override
  ConsumerState<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends ConsumerState<RegisterScreen>
    with _AuthSubmission {
  final _formKey = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _phone = TextEditingController(text: '+234');
  final _password = TextEditingController();
  final _confirm = TextEditingController();
  @override
  void dispose() {
    _name.dispose();
    _phone.dispose();
    _password.dispose();
    _confirm.dispose();
    super.dispose();
  }

  @override
  Set<String> get formFields => const {
    'name',
    'phone',
    'password',
    'password_confirmation',
    'referral_code',
  };

  Future<void> _submit() => submitAuth(
    _formKey,
    () => ref.read(authControllerProvider.notifier).register({
      'name': _name.text.trim(),
      'phone': _phone.text.trim(),
      'password': _password.text,
      'password_confirmation': _confirm.text,
      'locale': 'en',
      if (_referral.text.trim().isNotEmpty)
        'referral_code': _referral.text.trim(),
    }),
  );

  @override
  Widget build(BuildContext context) {
    return _AuthForm(
      heroAsset: 'assets/images/northern-nigeria-farmers-onboarding.png',
      heroAlignment: const Alignment(.35, -.1),
      heroTagline: 'Practical guidance for every field.',
      title: 'Start with your own farm',
      subtitle: 'No organisation is required. A cluster code is optional if someone invited you.',
      footer: _AuthSwitchLink(
        prompt: 'Already registered?',
        action: 'Sign in',
        onPressed: () => context.go('/sign-in'),
      ),
      children: [
        Form(
          key: _formKey,
          child: AutofillGroup(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                TextFormField(
                  controller: _name,
                  textCapitalization: TextCapitalization.words,
                  textInputAction: TextInputAction.next,
                  autofillHints: const [AutofillHints.name],
                  decoration: const InputDecoration(
                    labelText: 'Full name',
                    prefixIcon: Icon(Icons.person_outline),
                  ),
                  validator: (value) =>
                      serverError('name') ??
                      ((value?.trim().length ?? 0) < 2
                          ? 'Enter your full name'
                          : null),
                ),
                const SizedBox(height: 14),
                TextFormField(
                  controller: _phone,
                  keyboardType: TextInputType.phone,
                  textInputAction: TextInputAction.next,
                  autofillHints: const [AutofillHints.telephoneNumber],
                  decoration: const InputDecoration(
                    labelText: 'Phone number',
                    prefixIcon: Icon(Icons.phone_outlined),
                  ),
                  validator: (value) =>
                      serverError('phone') ?? _phoneValidator(value),
                ),
                const SizedBox(height: 14),
                _PasswordField(
                  controller: _password,
                  label: 'Create password',
                  helperText: 'At least 8 characters',
                  autofillHints: const [AutofillHints.newPassword],
                  validator: (value) =>
                      serverError('password') ??
                      ((value?.length ?? 0) < 8
                          ? 'Use at least 8 characters'
                          : null),
                ),
                const SizedBox(height: 14),
                _PasswordField(
                  controller: _confirm,
                  label: 'Confirm password',
                  prefixIcon: Icons.lock_reset_outlined,
                  autofillHints: const [AutofillHints.newPassword],
                  validator: (value) =>
                      serverError('password_confirmation') ??
                      (value != _password.text
                          ? 'Passwords do not match'
                          : null),
                ),
                const SizedBox(height: 14),
                TextFormField(
                  controller: _referral,
                  textCapitalization: TextCapitalization.characters,
                  textInputAction: TextInputAction.done,
                  decoration: const InputDecoration(
                    labelText: 'Cluster code (optional)',
                    prefixIcon: Icon(Icons.groups_outlined),
                  ),
                  validator: (_) => serverError('referral_code'),
                ),
                if (formError != null) ...[
                  const SizedBox(height: AgriSpacing.md),
                  _AuthErrorBanner(message: formError!),
                ],
                const SizedBox(height: AgriSpacing.lg),
                _AuthSubmitButton(
                  label: 'Create account',
                  loading: submitting,
                  onPressed: _submit,
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }
}

/// Runs a sign-in or registration request and keeps any failure on screen.
///
/// Server validation errors for [formFields] appear under their field; any
/// other error is shown as [formError] above the submit button.
mixin _AuthSubmission<T extends ConsumerStatefulWidget> on ConsumerState<T> {
  bool submitting = false;
  String? formError;
  Map<String, String> _serverErrors = const {};

  Set<String> get formFields;

  String? serverError(String field) => _serverErrors[field];

  Future<void> submitAuth(
    GlobalKey<FormState> formKey,
    Future<void> Function() request,
  ) async {
    if (submitting) {
      return;
    }
    _serverErrors = const {};
    if (!formKey.currentState!.validate()) {
      setState(() => formError = null);
      return;
    }
    FocusScope.of(context).unfocus();
    setState(() {
      formError = null;
      submitting = true;
    });
    try {
      await request();
    } on ApiException catch (error) {
      if (!mounted) {
        return;
      }
      final fieldErrors = <String, String>{};
      final otherErrors = <String>[];
      for (final MapEntry(:key, value: messages) in error.errors.entries) {
        if (messages.isEmpty) {
          continue;
        }
        if (formFields.contains(key)) {
          fieldErrors[key] = messages.first;
        } else {
          otherErrors.add(messages.first);
        }
      }
      setState(() {
        _serverErrors = fieldErrors;
        formError = otherErrors.isNotEmpty
            ? otherErrors.join('\n')
            : fieldErrors.isNotEmpty
            ? 'Please check the highlighted fields.'
            : error.message;
      });
      formKey.currentState?.validate();
    } catch (error) {
      if (mounted) {
        setState(() => formError = friendlyError(error));
      }
    } finally {
      if (mounted) {
        setState(() => submitting = false);
      }
    }
  }
}

class _AuthErrorBanner extends StatelessWidget {
  const _AuthErrorBanner({required this.message});

  final String message;

  @override
  Widget build(BuildContext context) => Semantics(
    liveRegion: true,
    child: Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AgriColors.claySoft,
        border: Border.all(color: const Color(0x339D4938)),
        borderRadius: BorderRadius.circular(AgriRadius.md),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Icon(
            Icons.error_outline_rounded,
            color: AgriColors.clay,
            size: 20,
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              message,
              style: const TextStyle(
                color: AgriColors.clay,
                fontWeight: FontWeight.w700,
                height: 1.35,
              ),
            ),
          ),
        ],
      ),
    ),
  );
}

class _AuthForm extends StatelessWidget {
  const _AuthForm({
    required this.heroAsset,
    required this.heroAlignment,
    required this.heroTagline,
    required this.title,
    required this.subtitle,
    required this.children,
    required this.footer,
  });

  static const _sheetRadius = 32.0;

  final String heroAsset;
  final Alignment heroAlignment;
  final String heroTagline;
  final String title;
  final String subtitle;
  final List<Widget> children;
  final Widget footer;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final heroHeight = (MediaQuery.sizeOf(context).height * .34).clamp(
      240.0,
      320.0,
    );
    final inputBorder = OutlineInputBorder(
      borderRadius: BorderRadius.circular(AgriRadius.md),
      borderSide: BorderSide.none,
    );
    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: SystemUiOverlayStyle.light,
      child: Scaffold(
        backgroundColor: AgriColors.paper,
        body: Stack(
          children: [
            Positioned(
              top: 0,
              left: 0,
              right: 0,
              height: heroHeight + _sheetRadius,
              child: Stack(
                fit: StackFit.expand,
                children: [
                  Image.asset(
                    heroAsset,
                    fit: BoxFit.cover,
                    alignment: heroAlignment,
                    excludeFromSemantics: true,
                    errorBuilder: (context, error, stackTrace) =>
                        const _FarmImageFallback(),
                  ),
                  const DecoratedBox(
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                        colors: [
                          Color(0x9912382C),
                          Color(0x2612382C),
                          Color(0xD912382C),
                        ],
                        stops: [0, .4, 1],
                      ),
                    ),
                  ),
                ],
              ),
            ),
            SafeArea(
              bottom: false,
              child: LayoutBuilder(
                builder: (context, constraints) => SingleChildScrollView(
                  keyboardDismissBehavior:
                      ScrollViewKeyboardDismissBehavior.onDrag,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      SizedBox(
                        height: heroHeight - MediaQuery.paddingOf(context).top,
                        child: Padding(
                          padding: const EdgeInsets.fromLTRB(
                            AgriSpacing.md,
                            AgriSpacing.sm,
                            AgriSpacing.lg,
                            AgriSpacing.lg,
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              _GlassIconButton(
                                icon: Icons.arrow_back_rounded,
                                tooltip: 'Back',
                                onPressed: () => context.go('/welcome'),
                              ),
                              const Spacer(),
                              const _BrandLockup(light: true),
                              const SizedBox(height: 12),
                              Text(
                                heroTagline,
                                style: theme.textTheme.titleLarge?.copyWith(
                                  color: Colors.white,
                                  height: 1.15,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                      ConstrainedBox(
                        constraints: BoxConstraints(
                          minHeight:
                              constraints.maxHeight -
                              heroHeight +
                              MediaQuery.paddingOf(context).top,
                        ),
                        child: DecoratedBox(
                          decoration: const BoxDecoration(
                            color: AgriColors.paper,
                            borderRadius: BorderRadius.vertical(
                              top: Radius.circular(_sheetRadius),
                            ),
                          ),
                          child: Padding(
                            padding: EdgeInsets.fromLTRB(
                              AgriSpacing.lg,
                              AgriSpacing.xl,
                              AgriSpacing.lg,
                              AgriSpacing.lg +
                                  MediaQuery.paddingOf(context).bottom,
                            ),
                            child: Theme(
                              data: theme.copyWith(
                                inputDecorationTheme: theme.inputDecorationTheme
                                    .copyWith(
                                      fillColor: AgriColors.canvas,
                                      prefixIconColor: AgriColors.muted,
                                      suffixIconColor: AgriColors.muted,
                                      border: inputBorder,
                                      enabledBorder: inputBorder,
                                      focusedBorder: inputBorder.copyWith(
                                        borderSide: const BorderSide(
                                          color: AgriColors.forest,
                                          width: 1.6,
                                        ),
                                      ),
                                      errorBorder: inputBorder.copyWith(
                                        borderSide: const BorderSide(
                                          color: AgriColors.clay,
                                        ),
                                      ),
                                      focusedErrorBorder: inputBorder.copyWith(
                                        borderSide: const BorderSide(
                                          color: AgriColors.clay,
                                          width: 1.6,
                                        ),
                                      ),
                                    ),
                              ),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.stretch,
                                children: [
                                  Text(
                                    title,
                                    style: theme.textTheme.headlineMedium,
                                  ),
                                  const SizedBox(height: 8),
                                  Text(
                                    subtitle,
                                    style: theme.textTheme.bodyLarge?.copyWith(
                                      color: AgriColors.muted,
                                    ),
                                  ),
                                  const SizedBox(height: AgriSpacing.lg),
                                  ...children,
                                  const SizedBox(height: AgriSpacing.md),
                                  footer,
                                ],
                              ),
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _PasswordField extends StatefulWidget {
  const _PasswordField({
    required this.controller,
    required this.label,
    required this.validator,
    this.prefixIcon = Icons.lock_outline,
    this.helperText,
    this.autofillHints,
    this.textInputAction = TextInputAction.next,
    this.onFieldSubmitted,
  });

  final TextEditingController controller;
  final String label;
  final FormFieldValidator<String> validator;
  final IconData prefixIcon;
  final String? helperText;
  final Iterable<String>? autofillHints;
  final TextInputAction textInputAction;
  final ValueChanged<String>? onFieldSubmitted;

  @override
  State<_PasswordField> createState() => _PasswordFieldState();
}

class _PasswordFieldState extends State<_PasswordField> {
  bool _obscured = true;

  @override
  Widget build(BuildContext context) => TextFormField(
    controller: widget.controller,
    obscureText: _obscured,
    enableSuggestions: false,
    autocorrect: false,
    autofillHints: widget.autofillHints,
    textInputAction: widget.textInputAction,
    onFieldSubmitted: widget.onFieldSubmitted,
    validator: widget.validator,
    decoration: InputDecoration(
      labelText: widget.label,
      helperText: widget.helperText,
      prefixIcon: Icon(widget.prefixIcon),
      suffixIcon: IconButton(
        tooltip: _obscured ? 'Show password' : 'Hide password',
        onPressed: () => setState(() => _obscured = !_obscured),
        icon: AnimatedSwitcher(
          duration: const Duration(milliseconds: 180),
          child: Icon(
            _obscured
                ? Icons.visibility_outlined
                : Icons.visibility_off_outlined,
            key: ValueKey(_obscured),
          ),
        ),
      ),
    ),
  );
}

class _AuthSubmitButton extends StatelessWidget {
  const _AuthSubmitButton({
    required this.label,
    required this.loading,
    required this.onPressed,
  });

  final String label;
  final bool loading;
  final VoidCallback onPressed;

  @override
  Widget build(BuildContext context) => FilledButton(
    style: FilledButton.styleFrom(
      minimumSize: const Size.fromHeight(56),
      backgroundColor: AgriColors.forest,
      foregroundColor: Colors.white,
      disabledBackgroundColor: AgriColors.grove,
      shape: const StadiumBorder(),
    ),
    onPressed: loading ? null : onPressed,
    child: loading
        ? const SizedBox.square(
            dimension: 22,
            child: CircularProgressIndicator(
              strokeWidth: 2,
              color: Colors.white,
            ),
          )
        : Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(label),
              const SizedBox(width: 8),
              const Icon(Icons.arrow_forward_rounded, size: 20),
            ],
          ),
  );
}

class _AuthSwitchLink extends StatelessWidget {
  const _AuthSwitchLink({
    required this.prompt,
    required this.action,
    required this.onPressed,
  });

  final String prompt;
  final String action;
  final VoidCallback onPressed;

  @override
  Widget build(BuildContext context) => Wrap(
    alignment: WrapAlignment.center,
    crossAxisAlignment: WrapCrossAlignment.center,
    children: [
      Text(prompt, style: const TextStyle(color: AgriColors.muted)),
      TextButton(
        style: TextButton.styleFrom(foregroundColor: AgriColors.forest),
        onPressed: onPressed,
        child: Text(action),
      ),
    ],
  );
}

class _GlassIconButton extends StatelessWidget {
  const _GlassIconButton({
    required this.icon,
    required this.tooltip,
    required this.onPressed,
  });

  final IconData icon;
  final String tooltip;
  final VoidCallback onPressed;

  @override
  Widget build(BuildContext context) => ClipOval(
    child: BackdropFilter(
      filter: ImageFilter.blur(sigmaX: 12, sigmaY: 12),
      child: Material(
        color: const Color(0x33FFFFFF),
        shape: const CircleBorder(side: BorderSide(color: Color(0x40FFFFFF))),
        child: IconButton(
          tooltip: tooltip,
          color: Colors.white,
          onPressed: onPressed,
          icon: Icon(icon),
        ),
      ),
    ),
  );
}

class _BrandMark extends StatelessWidget {
  const _BrandMark({required this.size});
  final double size;
  @override
  Widget build(BuildContext context) => Container(
    width: size,
    height: size,
    padding: EdgeInsets.all(size * .12),
    decoration: BoxDecoration(
      color: AgriColors.millet,
      borderRadius: BorderRadius.circular(size * .3),
    ),
    child: Image.asset('assets/images/agrishield-icon.png'),
  );
}

class _BrandLockup extends StatelessWidget {
  const _BrandLockup({required this.light});

  final bool light;

  @override
  Widget build(BuildContext context) => Row(
    children: [
      const _BrandMark(size: 48),
      const SizedBox(width: 12),
      Flexible(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'AGRISHIELD AI',
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                color: light ? Colors.white : AgriColors.ink,
                fontSize: 14,
                fontWeight: FontWeight.w900,
                letterSpacing: 1.1,
              ),
            ),
            Text(
              'Field intelligence for farmers',
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                color: light ? const Color(0xFFBDD0C2) : AgriColors.muted,
                fontSize: 12,
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ),
      ),
    ],
  );
}

class _OnboardingSlide {
  const _OnboardingSlide({
    required this.icon,
    required this.eyebrow,
    required this.title,
    required this.body,
    required this.semanticLabel,
    required this.asset,
    this.alignment = Alignment.center,
    this.lift = 0,
    this.credit,
  });

  final String asset;
  final Alignment alignment;

  /// Fraction of the screen height to raise the photo so its subject clears the copy.
  final double lift;
  final IconData icon;
  final String eyebrow;
  final String title;
  final String body;
  final String semanticLabel;
  final String? credit;
}

const _onboardingSlides = [
  _OnboardingSlide(
    asset: 'assets/images/northern-nigeria-farmers-onboarding.png',
    alignment: Alignment(.35, -.2),
    icon: Icons.location_on_outlined,
    eyebrow: 'Made for Northern Nigerian farms',
    title: 'Know what your crop needs. Act with confidence.',
    body: 'AgriShield helps you make the next field decision with practical, local guidance.',
    semanticLabel: 'Two farmers inspecting sorghum and maize at sunrise',
  ),
  _OnboardingSlide(
    asset: 'assets/images/jigawa-farmer.jpeg',
    alignment: Alignment(.1, 0),
    icon: Icons.eco_outlined,
    eyebrow: 'Crop checks',
    title: 'Spot crop problems before they spread.',
    body: 'Check your plants from your phone and get clear next steps for your field.',
    semanticLabel: 'A farmer in Jigawa checking young plants in sandy soil',
  ),
  _OnboardingSlide(
    asset: 'assets/images/kano-farmer-tending-field.webp',
    alignment: Alignment(.35, 0),
    lift: .09,
    icon: Icons.mic_none_rounded,
    eyebrow: 'Farm mapping · Voice guidance',
    title: 'Map your farm. Ask by voice.',
    body: 'Mark your farm boundaries and ask questions out loud when typing is not convenient.',
    semanticLabel: 'A farmer tending a green rice field in Kano State',
    credit: 'Photo: Photobyamin · Wikimedia Commons',
  ),
];

class _ParallaxSlideImage extends StatelessWidget {
  const _ParallaxSlideImage({
    required this.controller,
    required this.index,
    required this.slide,
  });

  final PageController controller;
  final int index;
  final _OnboardingSlide slide;

  @override
  Widget build(BuildContext context) {
    final image = Image.asset(
      slide.asset,
      fit: BoxFit.cover,
      alignment: slide.alignment,
      semanticLabel: slide.semanticLabel,
      errorBuilder: (context, error, stackTrace) => const _FarmImageFallback(),
    );
    return ClipRect(
      child: AnimatedBuilder(
        animation: controller,
        child: SizedBox.expand(child: image),
        builder: (context, child) {
          final position =
              controller.hasClients && controller.position.haveDimensions
              ? controller.page! - index
              : 0.0;
          final size = MediaQuery.sizeOf(context);
          return Transform.translate(
            offset: Offset(
              position * size.width * .1,
              -slide.lift * size.height,
            ),
            child: Transform.scale(scale: 1.2, child: child),
          );
        },
      ),
    );
  }
}

class _SlideCopy extends StatelessWidget {
  const _SlideCopy({super.key, required this.slide});

  final _OnboardingSlide slide;

  @override
  Widget build(BuildContext context) => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    mainAxisSize: MainAxisSize.min,
    children: [
      _GlassChip(icon: slide.icon, label: slide.eyebrow),
      const SizedBox(height: AgriSpacing.md),
      Text(
        slide.title,
        style: Theme.of(context).textTheme.displaySmall
            ?.copyWith(color: Colors.white, height: 1.04),
      ),
      const SizedBox(height: 12),
      Text(
        slide.body,
        style: const TextStyle(
          color: Color(0xFFD8E6DD),
          fontSize: 16,
          height: 1.45,
        ),
      ),
      if (slide.credit != null) ...[
        const SizedBox(height: 10),
        Text(
          slide.credit!,
          style: const TextStyle(
            color: Color(0xB3E7F0E9),
            fontSize: 10,
            fontWeight: FontWeight.w700,
          ),
        ),
      ],
    ],
  );
}

class _GlassChip extends StatelessWidget {
  const _GlassChip({required this.icon, required this.label});

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) => ClipRRect(
    borderRadius: BorderRadius.circular(999),
    child: BackdropFilter(
      filter: ImageFilter.blur(sigmaX: 12, sigmaY: 12),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        decoration: BoxDecoration(
          color: const Color(0x26FFFFFF),
          border: Border.all(color: const Color(0x33FFFFFF)),
          borderRadius: BorderRadius.circular(999),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, color: AgriColors.millet, size: 16),
            const SizedBox(width: 6),
            Flexible(
              child: Text(
                label.toUpperCase(),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  color: Color(0xFFF4E5BA),
                  fontSize: 11,
                  fontWeight: FontWeight.w900,
                  letterSpacing: .9,
                ),
              ),
            ),
          ],
        ),
      ),
    ),
  );
}

class _PageCounter extends StatelessWidget {
  const _PageCounter({required this.page, required this.total});

  final int page;
  final int total;

  @override
  Widget build(BuildContext context) => Text(
    '${(page + 1).toString().padLeft(2, '0')} / ${total.toString().padLeft(2, '0')}',
    style: const TextStyle(
      color: Color(0xFFF4E5BA),
      fontSize: 12,
      fontWeight: FontWeight.w800,
      letterSpacing: 1,
      fontFeatures: [FontFeature.tabularFigures()],
    ),
  );
}

class _PageIndicator extends StatelessWidget {
  const _PageIndicator({
    required this.page,
    required this.total,
    required this.onSelected,
  });

  final int page;
  final int total;
  final ValueChanged<int> onSelected;

  @override
  Widget build(BuildContext context) => Row(
    children: [
      for (var index = 0; index < total; index++)
        Semantics(
          button: true,
          selected: index == page,
          label: 'Slide ${index + 1} of $total',
          child: GestureDetector(
            behavior: HitTestBehavior.opaque,
            onTap: () => onSelected(index),
            child: Padding(
              padding: const EdgeInsets.symmetric(vertical: 10),
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 300),
                curve: Curves.easeOutCubic,
                margin: const EdgeInsets.only(right: 6),
                height: 6,
                width: index == page ? 28 : 8,
                decoration: BoxDecoration(
                  color: index == page
                      ? AgriColors.millet
                      : const Color(0x66FFFFFF),
                  borderRadius: BorderRadius.circular(3),
                ),
              ),
            ),
          ),
        ),
    ],
  );
}

class _FarmImageFallback extends StatelessWidget {
  const _FarmImageFallback();

  @override
  Widget build(BuildContext context) => const DecoratedBox(
    decoration: BoxDecoration(
      gradient: LinearGradient(
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
        colors: [Color(0xFF46745C), AgriColors.forest],
      ),
    ),
    child: Center(
      child: Icon(Icons.landscape_outlined, color: Color(0xFFDEBE67), size: 48),
    ),
  );
}

class _SplashTopline extends StatelessWidget {
  const _SplashTopline();

  @override
  Widget build(BuildContext context) => const Row(
    children: [
      Icon(Icons.radar_rounded, color: AgriColors.millet, size: 18),
      SizedBox(width: 8),
      Flexible(
        child: Text(
          'NORTHERN NIGERIA · FIELD READY',
          overflow: TextOverflow.ellipsis,
          style: TextStyle(
            color: Color(0xFFF4E5BA),
            fontSize: 11,
            fontWeight: FontWeight.w900,
            letterSpacing: 1.1,
          ),
        ),
      ),
    ],
  );
}

String? _phoneValidator(String? value) =>
    RegExp(r'^\+[1-9]\d{7,14}$').hasMatch((value ?? '').trim())
    ? null
    : 'Use international format, for example +234…';
