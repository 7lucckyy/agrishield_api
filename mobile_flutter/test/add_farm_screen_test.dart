import 'dart:typed_data';

import 'package:agrishield_ai/app/providers.dart';
import 'package:agrishield_ai/core/api/api_client.dart';
import 'package:agrishield_ai/core/theme/app_theme.dart';
import 'package:agrishield_ai/features/farms/farm_screens.dart';
import 'package:agrishield_ai/models/models.dart';
import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:latlong2/latlong.dart';

/// A 1×1 transparent PNG so map tiles render without network access.
final _transparentPng = Uint8List.fromList([
  0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A, 0x00, 0x00, 0x00, 0x0D, //
  0x49, 0x48, 0x44, 0x52, 0x00, 0x00, 0x00, 0x01, 0x00, 0x00, 0x00, 0x01,
  0x08, 0x06, 0x00, 0x00, 0x00, 0x1F, 0x15, 0xC4, 0x89, 0x00, 0x00, 0x00,
  0x0A, 0x49, 0x44, 0x41, 0x54, 0x78, 0x9C, 0x63, 0x00, 0x01, 0x00, 0x00,
  0x05, 0x00, 0x01, 0x0D, 0x0A, 0x2D, 0xB4, 0x00, 0x00, 0x00, 0x00, 0x49,
  0x45, 0x4E, 0x44, 0xAE, 0x42, 0x60, 0x82,
]);

class _BlankTileProvider extends TileProvider {
  @override
  ImageProvider getImage(TileCoordinates coordinates, TileLayer options) =>
      MemoryImage(_transparentPng);
}

class _RecordingApiClient extends ApiClient {
  Json? createdFarm;

  @override
  Future<Farm> createFarm(Json payload) async {
    createdFarm = payload;
    return const Farm(id: 'farm-9', name: 'North Field', status: 'active');
  }
}

Future<_RecordingApiClient> _openAddFarm(
  WidgetTester tester, {
  double initialZoom = 17,
}) async {
  tester.view.physicalSize = const Size(1170, 2532);
  tester.view.devicePixelRatio = 3;
  addTearDown(tester.view.reset);
  final api = _RecordingApiClient();
  final router = GoRouter(
    routes: [
      GoRoute(path: '/', builder: (_, _) => const Scaffold()),
      GoRoute(
        path: '/farms/new',
        builder: (_, _) => AddFarmScreen(
          tileProvider: _BlankTileProvider(),
          initialCenter: const LatLng(12.0, 8.5),
          initialZoom: initialZoom,
        ),
      ),
    ],
  );
  addTearDown(router.dispose);
  await tester.pumpWidget(
    ProviderScope(
      overrides: [
        apiClientProvider.overrideWithValue(api),
        farmsProvider.overrideWith((ref) async => const []),
      ],
      child: MaterialApp.router(
        theme: buildAgriShieldTheme(),
        routerConfig: router,
      ),
    ),
  );
  router.push('/farms/new');
  await tester.pumpAndSettle();
  return api;
}

/// The value shown above a label in the boundary panel, e.g. "Corners".
String _panelValue(WidgetTester tester, String label) => tester
    .widget<Text>(
      find
          .descendant(
            of: find
                .ancestor(of: find.text(label), matching: find.byType(Column))
                .first,
            matching: find.byType(Text),
          )
          .first,
    )
    .data!;

/// The visible map between the instruction card and the bottom panel.
Rect _openMapArea(WidgetTester tester) => Rect.fromLTRB(
  60,
  tester.getRect(find.text('Mark your farm')).bottom + 120,
  tester.getSize(find.byType(MaterialApp)).width - 60,
  tester.getRect(find.text('Corners')).top - 60,
);

/// Taps corners given as fractions (0–1) of the open map area.
Future<void> _tapCorners(WidgetTester tester, List<Offset> fractions) async {
  final area = _openMapArea(tester);
  for (final fraction in fractions) {
    await tester.tapAt(
      Offset(
        area.left + area.width * fraction.dx,
        area.top + area.height * fraction.dy,
      ),
    );
    await tester.pump(const Duration(milliseconds: 50));
  }
}

void main() {
  testWidgets('farmer registers a farm by tapping its corners on the map', (
    tester,
  ) async {
    final api = await _openAddFarm(tester);

    expect(find.text('Mark your farm'), findsOneWidget);
    expect(
      tester
          .widget<FilledButton>(find.widgetWithText(FilledButton, 'Continue'))
          .onPressed,
      isNull,
    );

    await _tapCorners(tester, const [
      Offset(0, 0),
      Offset(1, 0),
      Offset(1, 1),
      Offset(0, 1),
    ]);
    expect(_panelValue(tester, 'Corners'), '4');

    await tester.tap(find.byTooltip('Undo last corner'));
    await tester.pump();
    expect(_panelValue(tester, 'Corners'), '3');

    await tester.tap(find.text('Continue'));
    await tester.pumpAndSettle();
    expect(find.text('Farm details'), findsOneWidget);

    await tester.enterText(
      find.widgetWithText(TextFormField, 'Farm name'),
      'North Field',
    );
    await tester.enterText(
      find.widgetWithText(TextFormField, 'Village or local area'),
      'Zaria',
    );
    expect(nigerianStates, hasLength(37));
    await tester.ensureVisible(find.text('State'));
    await tester.tap(find.text('State'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Bauchi').last);
    await tester.pumpAndSettle();
    await tester.ensureVisible(find.text('Register farm'));
    await tester.tap(find.text('Register farm'));
    await tester.pumpAndSettle();

    final boundary = api.createdFarm?['boundary_geojson'] as Json?;
    expect(api.createdFarm?['name'], 'North Field');
    expect(api.createdFarm?['state'], 'Bauchi');
    expect(boundary?['type'], 'Polygon');
    final ring = (boundary!['coordinates'] as List).single as List;
    expect(ring, hasLength(4));
    expect(ring.first, ring.last);
  });

  testWidgets('corners are ignored until the map is zoomed in to a field', (
    tester,
  ) async {
    await _openAddFarm(tester, initialZoom: 6);

    await _tapCorners(tester, const [Offset(.5, .5)]);

    expect(
      find.text('Zoom in closer to your field, then tap its corners.'),
      findsOneWidget,
    );
    expect(_panelValue(tester, 'Corners'), '0');
    await tester.pump(const Duration(seconds: 5));
  });

  testWidgets('crossing edges are flagged and block continuing', (
    tester,
  ) async {
    await _openAddFarm(tester);

    await _tapCorners(tester, const [
      Offset(0, 0),
      Offset(1, 1),
      Offset(1, 0),
      Offset(0, 1),
    ]);

    expect(find.textContaining('Two edges cross'), findsOneWidget);
    expect(
      tester
          .widget<FilledButton>(find.widgetWithText(FilledButton, 'Continue'))
          .onPressed,
      isNull,
    );
  });
}
