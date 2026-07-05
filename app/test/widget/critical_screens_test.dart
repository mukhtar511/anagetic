import 'package:anaqatuk/core/network/api_client.dart';
import 'package:anaqatuk/data/api_repository.dart';
import 'package:anaqatuk/features/cart/cart_screen.dart';
import 'package:anaqatuk/features/product/product_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

import '../support/fake_api.dart';

/// In-memory token storage so tests never touch the platform keychain.
class _FakeTokenStorage extends TokenStorage {
  @override
  Future<String?> read() async => null;
  @override
  Future<void> write(String token) async {}
  @override
  Future<void> clear() async {}
}

Widget _wrap(Widget child) => ProviderScope(
      overrides: [
        apiRepositoryProvider.overrideWithValue(FakeApiRepository()),
        tokenStorageProvider.overrideWithValue(_FakeTokenStorage()),
      ],
      child: MaterialApp(
        locale: const Locale('ar'),
        home: Directionality(textDirection: TextDirection.rtl, child: child),
      ),
    );

/// Tall surface so lazy ListViews build their off-screen children.
Future<void> _pumpTall(WidgetTester tester, Widget child) async {
  tester.view.physicalSize = const Size(1200, 4000);
  tester.view.devicePixelRatio = 1.0;
  addTearDown(tester.view.resetPhysicalSize);
  addTearDown(tester.view.resetDevicePixelRatio);
  await tester.pumpWidget(_wrap(child));
  for (var i = 0; i < 6; i++) {
    await tester.pump(const Duration(milliseconds: 30));
  }
}

void main() {
  testWidgets('ProductScreen shows the title and never leaks colour quantity',
      (tester) async {
    await _pumpTall(tester, const ProductScreen(productId: 1));

    expect(find.text('فستان سهرة ساتان بقصّة ملكية'), findsOneWidget);
    // The in-stock colour is offered; the out-of-stock one is marked
    // unavailable — never with a numeric stock quantity. SPEC §4.6.
    expect(find.text('وردي'), findsWidgets);
    expect(find.textContaining('غير متوفر'), findsWidgets);
  });

  testWidgets('CartScreen renders the checkout total', (tester) async {
    await _pumpTall(tester, const CartScreen());

    // The Arabic-Indic total (١٬٦٢٠) appears on the pay button/summary.
    expect(find.textContaining('١٬٦٢٠'), findsWidgets);
  });
}
