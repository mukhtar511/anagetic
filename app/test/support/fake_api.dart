import 'package:anaqatuk/data/api_repository.dart';
import 'package:anaqatuk/models/models.dart';
import 'package:dio/dio.dart';

/// A canned ApiRepository for widget tests — no network. Extends the real one
/// (dummy Dio) and overrides only what the screens under test call.
class FakeApiRepository extends ApiRepository {
  FakeApiRepository() : super(Dio());

  @override
  Future<List<Region>> regions() async =>
      const [Region(id: 1, name: 'الرياض'), Region(id: 2, name: 'جدة')];

  @override
  Future<Product> product(int id) async => const Product(
        id: 1,
        title: 'فستان سهرة ساتان بقصّة ملكية',
        storeName: 'ريم كوتور',
        category: 'dress',
        code: 'A2605',
        rating: 4.8,
        commMode: 'chat',
        modes: [
          ProductMode(type: 'ready', price: 1250),
          ProductMode(type: 'rent', price: 180, deposit: 300),
        ],
        colors: [
          ProductColor(name: 'وردي', inStock: true),
          ProductColor(name: 'ذهبي', inStock: false), // out of stock — greyed, no qty
        ],
        addons: [ProductAddon(id: 1, name: 'ذيل للفستان', price: 150)],
      );

  @override
  Future<Map<String, dynamic>> cart() async => {
        'items': [
          {
            'id': 1,
            'title': 'فستان سهرة ساتان بقصّة ملكية',
            'store_name': 'ريم كوتور',
            'price': 1595,
            'meta': 'شراء بتعديل المقاسات · اللون: وردي',
          }
        ],
        'subtotal': 1595,
        'delivery': 25,
        'total': 1620,
      };

  @override
  Future<({num balance, List<WalletEntry> entries})> wallet() async => (
        balance: 434.4,
        entries: const [
          WalletEntry(text: '💰 دخل بيع — طلب #A-1039', amount: 1152.8),
          WalletEntry(text: '⬇ سحب لحسابك البنكي — مصرف الراجحي', amount: -1500),
        ],
      );

  @override
  Future<List<OrderModel>> orders({String? group}) async => const [
        OrderModel(
          id: 1,
          code: 'A-1043',
          status: 'in_progress',
          total: 1705,
          storeName: 'ريم كوتور',
          deliveryCode: '1111',
        ),
      ];
}
