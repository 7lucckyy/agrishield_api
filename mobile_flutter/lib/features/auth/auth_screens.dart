import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../app/providers.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/agri_widgets.dart';

const _northernFarmersImageUrl =
    'https://commons.wikimedia.org/wiki/Special:FilePath/Nigerian_farmers.jpg?width=1600';

class SplashScreen extends StatelessWidget {
  const SplashScreen({super.key});
  @override
  Widget build(BuildContext context) => Scaffold(
    backgroundColor: AgriColors.forest,
    body: Stack(
      fit: StackFit.expand,
      children: [
        _NorthernFarmersImage(
          fit: BoxFit.cover,
          alignment: Alignment.topCenter,
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
                    Text(
                      'Opening your field workspace',
                      style: TextStyle(color: Color(0xFFE7F0E9)),
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

class WelcomeScreen extends StatelessWidget {
  const WelcomeScreen({super.key});
  @override
  Widget build(BuildContext context) => Scaffold(
    backgroundColor: AgriColors.forest,
    body: SafeArea(
      child: LayoutBuilder(
        builder: (context, constraints) => SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const Row(
                children: [
                  Expanded(child: _BrandLockup(light: true)),
                  _OnboardingStep(),
                ],
              ),
              const SizedBox(height: 24),
              _OnboardingPhoto(height: constraints.maxHeight > 700 ? 292 : 186),
              const SizedBox(height: 18),
              const _TrustLine(),
              const SizedBox(height: 12),
              Text(
                'Know what your crop needs. Act with confidence.',
                style: Theme.of(context).textTheme.displaySmall
                    ?.copyWith(color: Colors.white, height: 1.04),
              ),
              const SizedBox(height: 10),
              const Text(
                'From farm boundaries to crop checks and voice guidance, AgriShield helps you make the next field decision with confidence.',
                style: TextStyle(
                  color: Color(0xFFD8E6DD),
                  fontSize: 16,
                  height: 1.45,
                ),
              ),
              const SizedBox(height: 22),
              const _CapabilityHeading(),
              const SizedBox(height: 10),
              const _ValueRail(),
              const SizedBox(height: 24),
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
  @override
  void dispose() {
    _name.dispose();
    _phone.dispose();
    _password.dispose();
    _confirm.dispose();
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
      subtitle: 'Create an account to start managing your own farm.',
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

class _BrandLockup extends StatelessWidget {
  const _BrandLockup({required this.light});

  final bool light;

  @override
  Widget build(BuildContext context) => Row(
    children: [
      const _BrandMark(size: 48),
      const SizedBox(width: 12),
      Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'AGRISHIELD AI',
            style: TextStyle(
              color: light ? Colors.white : AgriColors.ink,
              fontSize: 14,
              fontWeight: FontWeight.w900,
              letterSpacing: 1.1,
            ),
          ),
          Text(
            'Field intelligence for farmers',
            style: TextStyle(
              color: light ? const Color(0xFFBDD0C2) : AgriColors.muted,
              fontSize: 12,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    ],
  );
}

class _OnboardingPhoto extends StatelessWidget {
  const _OnboardingPhoto({required this.height});

  final double height;

  @override
  Widget build(BuildContext context) => Semantics(
    image: true,
    label: 'Nigerian farmers working in a field',
    child: Container(
      decoration: BoxDecoration(
        border: Border.all(color: const Color(0x40759383)),
        borderRadius: BorderRadius.circular(AgriRadius.lg),
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(AgriRadius.lg - 1),
        child: Stack(
          children: [
            SizedBox(
              height: height,
              width: double.infinity,
              child: const _NorthernFarmersImage(
                fit: BoxFit.cover,
                alignment: Alignment.center,
              ),
            ),
            const Positioned.fill(
              child: DecoratedBox(
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: [Color(0x0012382C), Color(0xB812382C)],
                    stops: [.42, 1],
                  ),
                ),
              ),
            ),
            Positioned(
              top: 12,
              left: 12,
              child: Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 7,
                ),
                decoration: BoxDecoration(
                  color: const Color(0xE8F4E5BA),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: const Text(
                  'FIELD NOTE  ·  01',
                  style: TextStyle(
                    color: AgriColors.ink,
                    fontSize: 10,
                    fontWeight: FontWeight.w900,
                    letterSpacing: .8,
                  ),
                ),
              ),
            ),
            Positioned(
              left: 12,
              bottom: 12,
              child: Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 7,
                ),
                decoration: BoxDecoration(
                  color: const Color(0xD912382C),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: const Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(
                      Icons.wb_sunny_outlined,
                      color: AgriColors.millet,
                      size: 16,
                    ),
                    SizedBox(width: 6),
                    Text(
                      'Built for the field',
                      style: TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const Positioned(
              right: 12,
              bottom: 14,
              child: Text(
                'Photo: Mike Blyth · CC BY 2.5',
                style: TextStyle(
                  color: Color(0xFFE7F0E9),
                  fontSize: 10,
                  fontWeight: FontWeight.w700,
                  shadows: [Shadow(color: Colors.black54, blurRadius: 4)],
                ),
              ),
            ),
          ],
        ),
      ),
    ),
  );
}

class _NorthernFarmersImage extends StatelessWidget {
  const _NorthernFarmersImage({required this.fit, required this.alignment});

  final BoxFit fit;
  final Alignment alignment;

  @override
  Widget build(BuildContext context) => Semantics(
    image: true,
    label: 'Nigerian farmers working in a field',
    child: Image.network(
      _northernFarmersImageUrl,
      fit: fit,
      alignment: alignment,
      excludeFromSemantics: true,
      loadingBuilder: (context, child, loading) {
        if (loading == null) return child;
        return const _FarmImageFallback(loading: true);
      },
      errorBuilder: (context, error, stackTrace) => const _FarmImageFallback(),
    ),
  );
}

class _FarmImageFallback extends StatelessWidget {
  const _FarmImageFallback({this.loading = false});

  final bool loading;

  @override
  Widget build(BuildContext context) => DecoratedBox(
    decoration: const BoxDecoration(
      gradient: LinearGradient(
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
        colors: [Color(0xFF46745C), AgriColors.forest],
      ),
    ),
    child: Center(
      child: Icon(
        loading ? Icons.agriculture_outlined : Icons.landscape_outlined,
        color: Color(0xFFDEBE67),
        size: 48,
      ),
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
      Text(
        'NORTHERN NIGERIA · FIELD READY',
        style: TextStyle(
          color: Color(0xFFF4E5BA),
          fontSize: 11,
          fontWeight: FontWeight.w900,
          letterSpacing: 1.1,
        ),
      ),
    ],
  );
}

class _TrustLine extends StatelessWidget {
  const _TrustLine();

  @override
  Widget build(BuildContext context) => const Row(
    children: [
      Icon(Icons.location_on_outlined, color: AgriColors.millet, size: 18),
      SizedBox(width: 7),
      Expanded(
        child: Text(
          'Made for Northern Nigerian farms',
          style: TextStyle(
            color: Color(0xFFF4E5BA),
            fontWeight: FontWeight.w800,
          ),
        ),
      ),
    ],
  );
}

class _OnboardingStep extends StatelessWidget {
  const _OnboardingStep();

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
    decoration: BoxDecoration(
      color: const Color(0x29102019),
      border: Border.all(color: const Color(0x33759383)),
      borderRadius: BorderRadius.circular(AgriRadius.sm),
    ),
    child: const Text(
      'START HERE',
      style: TextStyle(
        color: Color(0xFFF4E5BA),
        fontSize: 10,
        fontWeight: FontWeight.w900,
        letterSpacing: .9,
      ),
    ),
  );
}

class _CapabilityHeading extends StatelessWidget {
  const _CapabilityHeading();

  @override
  Widget build(BuildContext context) => const Row(
    children: [
      Text(
        'YOUR FIELD TOOLKIT',
        style: TextStyle(
          color: Color(0xFFF4E5BA),
          fontSize: 11,
          fontWeight: FontWeight.w900,
          letterSpacing: 1,
        ),
      ),
      SizedBox(width: 10),
      Expanded(child: Divider(color: Color(0x33759383))),
    ],
  );
}

class _ValueRail extends StatelessWidget {
  const _ValueRail();

  @override
  Widget build(BuildContext context) => const Row(
    children: [
      Expanded(
        child: _ValueItem(icon: Icons.map_outlined, label: 'Map your farm'),
      ),
      SizedBox(width: 8),
      Expanded(
        child: _ValueItem(icon: Icons.mic_none_rounded, label: 'Ask by voice'),
      ),
      SizedBox(width: 8),
      Expanded(
        child: _ValueItem(icon: Icons.eco_outlined, label: 'Check crops'),
      ),
    ],
  );
}

class _ValueItem extends StatelessWidget {
  const _ValueItem({required this.icon, required this.label});

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) => Container(
    height: 82,
    padding: const EdgeInsets.all(10),
    decoration: BoxDecoration(
      color: const Color(0x29102019),
      border: Border.all(color: const Color(0x33759383)),
      borderRadius: BorderRadius.circular(AgriRadius.sm),
    ),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, color: AgriColors.millet, size: 20),
        const Spacer(),
        Text(
          label,
          style: const TextStyle(
            color: Colors.white,
            fontSize: 12,
            fontWeight: FontWeight.w700,
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
