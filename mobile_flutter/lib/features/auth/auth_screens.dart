import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../app/providers.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/agri_widgets.dart';

class SplashScreen extends StatelessWidget {
  const SplashScreen({super.key});
  @override
  Widget build(BuildContext context) => const Scaffold(
    backgroundColor: AgriColors.forest,
    body: Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          _BrandMark(size: 92),
          SizedBox(height: 22),
          Text(
            'AgriShield AI',
            style: TextStyle(
              color: Colors.white,
              fontWeight: FontWeight.w800,
              fontSize: 28,
            ),
          ),
          SizedBox(height: 24),
          CircularProgressIndicator(color: AgriColors.millet),
        ],
      ),
    ),
  );
}

class WelcomeScreen extends StatelessWidget {
  const WelcomeScreen({super.key});
  @override
  Widget build(BuildContext context) => Scaffold(
    backgroundColor: AgriColors.forest,
    body: SafeArea(
      child: LayoutBuilder(
        builder: (context, constraints) => ListView(
          padding: const EdgeInsets.all(AgriSpacing.lg),
          children: [
            const Row(
              children: [
                _BrandMark(size: 54),
                SizedBox(width: 12),
                Text(
                  'AGRISHIELD AI',
                  style: TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.w900,
                    letterSpacing: 1.2,
                  ),
                ),
              ],
            ),
            SizedBox(
              height: constraints.maxHeight > 720
                  ? constraints.maxHeight - 650
                  : AgriSpacing.lg,
            ),
            const _FieldGraphic(),
            const SizedBox(height: AgriSpacing.xl),
            Text(
              'Know what your crop needs. Act with confidence.',
              style: Theme.of(context).textTheme.displaySmall
                  ?.copyWith(color: Colors.white, height: 1.04),
            ),
            const SizedBox(height: AgriSpacing.md),
            const Text(
              'Register your farm, check crop symptoms, ask questions by voice, and get timely field guidance.',
              style: TextStyle(
                color: Color(0xFFD8E6DD),
                fontSize: 17,
                height: 1.45,
              ),
            ),
            const SizedBox(height: AgriSpacing.xl),
            FilledButton(
              style: FilledButton.styleFrom(
                backgroundColor: AgriColors.millet,
                foregroundColor: AgriColors.ink,
              ),
              onPressed: () => context.go('/register'),
              child: const Text('Create my farmer account'),
            ),
            const SizedBox(height: 10),
            OutlinedButton(
              style: OutlinedButton.styleFrom(
                minimumSize: const Size.fromHeight(56),
                foregroundColor: Colors.white,
                side: const BorderSide(color: Color(0xFF759383)),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(AgriRadius.sm),
                ),
              ),
              onPressed: () => context.go('/sign-in'),
              child: const Text('I already have an account'),
            ),
          ],
        ),
      ),
    ),
  );
}

class SignInScreen extends ConsumerStatefulWidget {
  const SignInScreen({super.key});
  @override
  ConsumerState<SignInScreen> createState() => _SignInScreenState();
}

class _SignInScreenState extends ConsumerState<SignInScreen> {
  final _formKey = GlobalKey<FormState>();
  final _phone = TextEditingController(text: '+234');
  final _password = TextEditingController();
  bool _hidden = true;
  @override
  void dispose() {
    _phone.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    await ref
        .read(authControllerProvider.notifier)
        .signIn(_phone.text, _password.text);
    if (!mounted) return;
    final result = ref.read(authControllerProvider);
    if (result.hasError) showMessage(context, friendlyError(result.error));
  }

  @override
  Widget build(BuildContext context) {
    final loading = ref.watch(authControllerProvider).isLoading;
    return _AuthForm(
      title: 'Welcome back',
      subtitle: 'Sign in with the phone number on your farmer account.',
      footer: TextButton(
        onPressed: () => context.go('/register'),
        child: const Text('New to AgriShield? Create an account'),
      ),
      children: [
        Form(
          key: _formKey,
          child: Column(
            children: [
              TextFormField(
                controller: _phone,
                keyboardType: TextInputType.phone,
                autofillHints: const [AutofillHints.telephoneNumber],
                decoration: const InputDecoration(
                  labelText: 'Phone number',
                  prefixIcon: Icon(Icons.phone_outlined),
                ),
                validator: _phoneValidator,
              ),
              const SizedBox(height: 14),
              TextFormField(
                controller: _password,
                obscureText: _hidden,
                autofillHints: const [AutofillHints.password],
                decoration: InputDecoration(
                  labelText: 'Password',
                  prefixIcon: const Icon(Icons.lock_outline),
                  suffixIcon: IconButton(
                    onPressed: () => setState(() => _hidden = !_hidden),
                    icon: Icon(
                      _hidden
                          ? Icons.visibility_outlined
                          : Icons.visibility_off_outlined,
                    ),
                  ),
                ),
                validator: (value) =>
                    (value?.length ?? 0) < 8 ? 'Enter your password' : null,
              ),
              const SizedBox(height: 22),
              FilledButton(
                onPressed: loading ? null : _submit,
                child: loading
                    ? const SizedBox.square(
                        dimension: 22,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Text('Sign in'),
              ),
            ],
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

class _RegisterScreenState extends ConsumerState<RegisterScreen> {
  final _formKey = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _phone = TextEditingController(text: '+234');
  final _password = TextEditingController();
  final _confirm = TextEditingController();
  final _referral = TextEditingController();
  @override
  void dispose() {
    _name.dispose();
    _phone.dispose();
    _password.dispose();
    _confirm.dispose();
    _referral.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    await ref.read(authControllerProvider.notifier).register({
      'name': _name.text.trim(),
      'phone': _phone.text.trim(),
      'password': _password.text,
      'password_confirmation': _confirm.text,
      'locale': 'en',
      if (_referral.text.trim().isNotEmpty)
        'referral_code': _referral.text.trim(),
    });
    if (!mounted) return;
    final result = ref.read(authControllerProvider);
    if (result.hasError) showMessage(context, friendlyError(result.error));
  }

  @override
  Widget build(BuildContext context) {
    final loading = ref.watch(authControllerProvider).isLoading;
    return _AuthForm(
      title: 'Start with your own farm',
      subtitle: 'No organisation is required. A cluster code is optional if someone invited you.',
      footer: TextButton(
        onPressed: () => context.go('/sign-in'),
        child: const Text('Already registered? Sign in'),
      ),
      children: [
        Form(
          key: _formKey,
          child: Column(
            children: [
              TextFormField(
                controller: _name,
                textCapitalization: TextCapitalization.words,
                decoration: const InputDecoration(
                  labelText: 'Full name',
                  prefixIcon: Icon(Icons.person_outline),
                ),
                validator: (value) => (value?.trim().length ?? 0) < 2
                    ? 'Enter your full name'
                    : null,
              ),
              const SizedBox(height: 14),
              TextFormField(
                controller: _phone,
                keyboardType: TextInputType.phone,
                decoration: const InputDecoration(
                  labelText: 'Phone number',
                  prefixIcon: Icon(Icons.phone_outlined),
                ),
                validator: _phoneValidator,
              ),
              const SizedBox(height: 14),
              TextFormField(
                controller: _password,
                obscureText: true,
                decoration: const InputDecoration(
                  labelText: 'Create password',
                  prefixIcon: Icon(Icons.lock_outline),
                ),
                validator: (value) => (value?.length ?? 0) < 8
                    ? 'Use at least 8 characters'
                    : null,
              ),
              const SizedBox(height: 14),
              TextFormField(
                controller: _confirm,
                obscureText: true,
                decoration: const InputDecoration(
                  labelText: 'Confirm password',
                  prefixIcon: Icon(Icons.lock_reset_outlined),
                ),
                validator: (value) =>
                    value != _password.text ? 'Passwords do not match' : null,
              ),
              const SizedBox(height: 14),
              TextFormField(
                controller: _referral,
                textCapitalization: TextCapitalization.characters,
                decoration: const InputDecoration(
                  labelText: 'Cluster code (optional)',
                  prefixIcon: Icon(Icons.groups_outlined),
                ),
              ),
              const SizedBox(height: 22),
              FilledButton(
                onPressed: loading ? null : _submit,
                child: loading
                    ? const SizedBox.square(
                        dimension: 22,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Text('Create account'),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

class _AuthForm extends StatelessWidget {
  const _AuthForm({
    required this.title,
    required this.subtitle,
    required this.children,
    required this.footer,
  });
  final String title;
  final String subtitle;
  final List<Widget> children;
  final Widget footer;
  @override
  Widget build(BuildContext context) => Scaffold(
    body: SafeArea(
      child: ListView(
        padding: const EdgeInsets.all(AgriSpacing.lg),
        children: [
          Align(
            alignment: Alignment.centerLeft,
            child: IconButton(
              onPressed: () => context.go('/welcome'),
              icon: const Icon(Icons.arrow_back_rounded),
            ),
          ),
          const SizedBox(height: AgriSpacing.md),
          const _BrandMark(size: 64),
          const SizedBox(height: AgriSpacing.lg),
          Text(title, style: Theme.of(context).textTheme.displaySmall),
          const SizedBox(height: 8),
          Text(
            subtitle,
            style: Theme.of(context).textTheme.bodyLarge
                ?.copyWith(color: AgriColors.muted),
          ),
          const SizedBox(height: AgriSpacing.xl),
          ...children,
          const SizedBox(height: 12),
          footer,
        ],
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

class _FieldGraphic extends StatelessWidget {
  const _FieldGraphic();
  @override
  Widget build(BuildContext context) => SizedBox(
    height: 132,
    child: Stack(
      children: [
        Positioned(
          left: 0,
          right: 0,
          bottom: 0,
          child: Container(
            height: 80,
            decoration: const BoxDecoration(
              color: AgriColors.grove,
              borderRadius: BorderRadius.only(
                topLeft: Radius.circular(80),
                topRight: Radius.circular(20),
              ),
            ),
          ),
        ),
        Positioned(
          left: 28,
          bottom: 24,
          child: Transform.rotate(
            angle: -.35,
            child: const Icon(
              Icons.eco_rounded,
              size: 96,
              color: AgriColors.millet,
            ),
          ),
        ),
        const Positioned(
          right: 22,
          top: 5,
          child: Icon(
            Icons.wb_sunny_rounded,
            color: AgriColors.millet,
            size: 48,
          ),
        ),
      ],
    ),
  );
}

String? _phoneValidator(String? value) =>
    RegExp(r'^\+[1-9]\d{7,14}$').hasMatch((value ?? '').trim())
    ? null
    : 'Use international format, for example +234…';
