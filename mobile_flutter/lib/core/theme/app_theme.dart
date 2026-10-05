import 'package:flutter/material.dart';

abstract final class AgriColors {
  static const forest = Color(0xFF12382C);
  static const grove = Color(0xFF215D46);
  static const leaf = Color(0xFF3F7B55);
  static const leafSoft = Color(0xFFE3ECE3);
  static const millet = Color(0xFFD8A93D);
  static const milletSoft = Color(0xFFF5E9C9);
  static const sky = Color(0xFF356B8C);
  static const skySoft = Color(0xFFE2EDF3);
  static const water = Color(0xFF2F7184);
  static const waterSoft = Color(0xFFDCECF0);
  static const soil = Color(0xFF7C593F);
  static const soilSoft = Color(0xFFEFE5DC);
  static const indigo = Color(0xFF344A69);
  static const indigoSoft = Color(0xFFE4E9F0);
  static const clay = Color(0xFF9D4938);
  static const claySoft = Color(0xFFF2E2DE);
  static const critical = Color(0xFF8B2F2F);
  static const criticalSoft = Color(0xFFF2DDDC);
  static const canvas = Color(0xFFF4F5EF);
  static const paper = Color(0xFFFCFCF8);
  static const ink = Color(0xFF17231E);
  static const muted = Color(0xFF5B6961);
  static const line = Color(0xFFD3D9D2);
  static const lineStrong = Color(0xFFB3BEB6);
}

abstract final class AgriSpacing {
  static const xs = 4.0;
  static const sm = 8.0;
  static const md = 16.0;
  static const lg = 24.0;
  static const xl = 32.0;
  static const xxl = 48.0;
}

abstract final class AgriRadius {
  static const sm = 8.0;
  static const md = 14.0;
  static const lg = 20.0;
}

ThemeData buildAgriShieldTheme() {
  final scheme = ColorScheme.fromSeed(
    seedColor: AgriColors.forest,
    brightness: Brightness.light,
    primary: AgriColors.forest,
    secondary: AgriColors.millet,
    tertiary: AgriColors.indigo,
    surface: AgriColors.paper,
    error: AgriColors.clay,
  );
  final text = Typography.material2021().black.apply(
    bodyColor: AgriColors.ink,
    displayColor: AgriColors.ink,
    fontFamily: 'Avenir Next',
  );

  return ThemeData(
    useMaterial3: true,
    colorScheme: scheme,
    scaffoldBackgroundColor: AgriColors.canvas,
    textTheme: text.copyWith(
      displaySmall: text.displaySmall?.copyWith(
        fontSize: 32,
        fontWeight: FontWeight.w800,
        letterSpacing: -1,
      ),
      headlineMedium: text.headlineMedium?.copyWith(
        fontSize: 24,
        fontWeight: FontWeight.w700,
        letterSpacing: -.5,
        height: 1.15,
      ),
      titleLarge: text.titleLarge?.copyWith(
        fontSize: 18,
        fontWeight: FontWeight.w700,
        letterSpacing: -.2,
      ),
      titleMedium: text.titleMedium?.copyWith(
        fontSize: 15,
        fontWeight: FontWeight.w700,
      ),
      bodyLarge: text.bodyLarge?.copyWith(height: 1.45, fontSize: 15),
      bodyMedium: text.bodyMedium?.copyWith(height: 1.45, fontSize: 14),
      bodySmall: text.bodySmall?.copyWith(
        height: 1.4,
        fontSize: 12,
        color: AgriColors.muted,
      ),
      labelLarge: text.labelLarge?.copyWith(
        fontSize: 14,
        fontWeight: FontWeight.w700,
      ),
    ),
    appBarTheme: const AppBarTheme(
      backgroundColor: AgriColors.canvas,
      foregroundColor: AgriColors.ink,
      elevation: 0,
      centerTitle: false,
    ),
    cardTheme: const CardThemeData(
      color: AgriColors.paper,
      elevation: 0,
      clipBehavior: Clip.antiAlias,
      margin: EdgeInsets.zero,
      shape: RoundedRectangleBorder(
        side: BorderSide(color: AgriColors.line),
        borderRadius: BorderRadius.all(Radius.circular(AgriRadius.md)),
      ),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: AgriColors.paper,
      contentPadding: const EdgeInsets.symmetric(horizontal: 18, vertical: 18),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(AgriRadius.sm),
        borderSide: const BorderSide(color: AgriColors.line),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(AgriRadius.sm),
        borderSide: const BorderSide(color: AgriColors.line),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(AgriRadius.sm),
        borderSide: const BorderSide(color: AgriColors.forest, width: 2),
      ),
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        minimumSize: const Size.fromHeight(56),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AgriRadius.sm),
        ),
        textStyle: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700),
      ),
    ),
    outlinedButtonTheme: OutlinedButtonThemeData(
      style: OutlinedButton.styleFrom(
        minimumSize: const Size.fromHeight(52),
        side: const BorderSide(color: AgriColors.lineStrong),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AgriRadius.sm),
        ),
        textStyle: const TextStyle(fontWeight: FontWeight.w700),
      ),
    ),
    iconButtonTheme: IconButtonThemeData(
      style: IconButton.styleFrom(minimumSize: const Size.square(48)),
    ),
    navigationBarTheme: NavigationBarThemeData(
      height: 68,
      backgroundColor: AgriColors.paper,
      indicatorColor: AgriColors.leafSoft,
      elevation: 0,
      labelBehavior: NavigationDestinationLabelBehavior.alwaysShow,
      labelTextStyle: WidgetStateProperty.resolveWith(
        (states) => TextStyle(
          fontSize: 11,
          fontWeight: states.contains(WidgetState.selected)
              ? FontWeight.w700
              : FontWeight.w500,
          color: states.contains(WidgetState.selected)
              ? AgriColors.forest
              : AgriColors.muted,
        ),
      ),
      iconTheme: WidgetStateProperty.resolveWith(
        (states) => IconThemeData(
          size: 22,
          color: states.contains(WidgetState.selected)
              ? AgriColors.forest
              : AgriColors.muted,
        ),
      ),
    ),
    dividerTheme: const DividerThemeData(color: AgriColors.line, thickness: 1),
    snackBarTheme: const SnackBarThemeData(
      behavior: SnackBarBehavior.floating,
      backgroundColor: AgriColors.ink,
      contentTextStyle: TextStyle(color: Colors.white),
    ),
  );
}
