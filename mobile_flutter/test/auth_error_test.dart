import 'package:agrishield_ai/app/app.dart';
import 'package:agrishield_ai/app/providers.dart';
import 'package:agrishield_ai/core/api/api_client.dart';
import 'package:agrishield_ai/features/auth/auth_screens.dart';
import 'package:agrishield_ai/models/models.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

class _RejectingAuthController extends AuthController {
  _RejectingAuthController(this.error);

  final ApiException error;
  int attempts = 0;

  @override
  Future<AuthState> build() async => const AuthState(isRestoring: false);

  @override
  Future<void> signIn(String phone, String password) async {
    attempts++;
    throw error;
  }

  @override
  Future<void> register(Json payload) async {
    attempts++;
    throw error;
  }
}

Future<void> _openRoute(
  WidgetTester tester,
  _RejectingAuthController controller,
  String location,
) async {
  tester.view.physicalSize = const Size(1170, 2532);
  tester.view.devicePixelRatio = 3;
  addTearDown(tester.view.reset);
  await tester.pumpWidget(
    ProviderScope(
      overrides: [authControllerProvider.overrideWith(() => controller)],
      child: const AgriShieldApp(),
    ),
  );
  await tester.pump();
  ProviderScope.containerOf(tester.element(find.byType(AgriShieldApp)))
      .read(routerProvider)
      .go(location);
  await tester.pump(const Duration(milliseconds: 500));
}

void main() {
  testWidgets('invalid credentials stay on sign in and show the API message', (
    tester,
  ) async {
    final controller = _RejectingAuthController(
      const ApiException(
        'The provided credentials are invalid.',
        statusCode: 401,
      ),
    );
    await _openRoute(tester, controller, '/sign-in');

    await tester.enterText(
      find.widgetWithText(TextFormField, 'Phone number'),
      '+2348012345678',
    );
    await tester.enterText(
      find.widgetWithText(TextFormField, 'Password'),
      'wrong-password',
    );
    await tester.tap(find.text('Sign in'));
    await tester.pump(const Duration(milliseconds: 500));

    expect(controller.attempts, 1);
    expect(find.byType(SignInScreen), findsOneWidget);
    expect(find.byType(WelcomeScreen), findsNothing);
    expect(find.text('The provided credentials are invalid.'), findsOneWidget);
  });

  testWidgets('registration validation errors appear under their fields', (
    tester,
  ) async {
    final controller = _RejectingAuthController(
      const ApiException(
        'Validation failed.',
        statusCode: 422,
        errors: {
          'password': ['The password field must contain at least one number.'],
        },
      ),
    );
    await _openRoute(tester, controller, '/register');

    await tester.enterText(
      find.widgetWithText(TextFormField, 'Full name'),
      'Amina Bello',
    );
    await tester.enterText(
      find.widgetWithText(TextFormField, 'Phone number'),
      '+2348012345678',
    );
    await tester.enterText(
      find.widgetWithText(TextFormField, 'Create password'),
      'longpassword',
    );
    await tester.enterText(
      find.widgetWithText(TextFormField, 'Confirm password'),
      'longpassword',
    );
    await tester.ensureVisible(find.text('Create account'));
    await tester.pump(const Duration(milliseconds: 300));
    await tester.tap(find.text('Create account'));
    await tester.pump(const Duration(milliseconds: 500));

    expect(controller.attempts, 1);
    expect(find.byType(RegisterScreen), findsOneWidget);
    expect(
      find.text('The password field must contain at least one number.'),
      findsOneWidget,
    );
    expect(find.text('Please check the highlighted fields.'), findsOneWidget);
  });
}
