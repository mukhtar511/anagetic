/// Plain DTOs mirroring the API resources. Money stays `num` (Latin);
/// display formatting happens in the UI via [formatSar]. SPEC §0.
library;

class Region {
  const Region({required this.id, required this.name});
  final int id;
  final String name;

  factory Region.fromJson(Map<String, dynamic> j) =>
      Region(id: j['id'] as int, name: j['name'] as String);
}

class ProductColor {
  const ProductColor({required this.name, this.hex, required this.inStock});
  final String name;
  final String? hex;
  final bool inStock; // qty is NEVER exposed — only availability. SPEC §4.6

  factory ProductColor.fromJson(Map<String, dynamic> j) => ProductColor(
        name: j['name'] as String,
        hex: j['hex'] as String?,
        inStock: (j['in_stock'] as bool?) ?? true,
      );
}

class ProductMode {
  const ProductMode({required this.type, required this.price, this.deposit});
  final String type; // ready|custom|rent
  final num price;
  final num? deposit;

  factory ProductMode.fromJson(Map<String, dynamic> j) => ProductMode(
        type: j['type'] as String,
        price: j['price'] as num,
        deposit: j['deposit'] as num?,
      );
}

class ProductAddon {
  const ProductAddon({required this.id, required this.name, required this.price});
  final int id;
  final String name;
  final num price;

  factory ProductAddon.fromJson(Map<String, dynamic> j) => ProductAddon(
        id: j['id'] as int,
        name: j['name'] as String,
        price: j['price'] as num,
      );
}

class Product {
  const Product({
    required this.id,
    required this.title,
    required this.storeName,
    required this.category,
    this.region,
    this.code,
    this.rating,
    this.salePrice,
    this.rentPrice,
    this.badges = const [],
    this.nearRegion = false,
    this.modes = const [],
    this.colors = const [],
    this.addons = const [],
    this.commMode,
    this.sellerPhone,
  });

  final int id;
  final String title;
  final String storeName;
  final String category;
  final String? region;
  final String? code;
  final num? rating;
  final num? salePrice;
  final num? rentPrice;
  final List<String> badges;
  final bool nearRegion;
  final List<ProductMode> modes;
  final List<ProductColor> colors;
  final List<ProductAddon> addons;
  final String? commMode; // chat|call|payfirst
  final String? sellerPhone; // present only when comm_mode=call & verified. SPEC §4.8

  factory Product.fromJson(Map<String, dynamic> j) => Product(
        id: j['id'] as int,
        title: j['title'] as String,
        storeName: (j['store_name'] ?? j['store']?['name'] ?? '') as String,
        category: (j['category'] ?? '') as String,
        region: j['region'] as String?,
        code: j['code'] as String?,
        rating: j['rating'] as num?,
        salePrice: j['sale_price'] as num?,
        rentPrice: j['rent_price'] as num?,
        badges: (j['badges'] as List?)?.cast<String>() ?? const [],
        nearRegion: (j['near_region'] as bool?) ?? false,
        modes: (j['modes'] as List?)
                ?.map((e) => ProductMode.fromJson(e as Map<String, dynamic>))
                .toList() ??
            const [],
        colors: (j['colors'] as List?)
                ?.map((e) => ProductColor.fromJson(e as Map<String, dynamic>))
                .toList() ??
            const [],
        addons: (j['addons'] as List?)
                ?.map((e) => ProductAddon.fromJson(e as Map<String, dynamic>))
                .toList() ??
            const [],
        commMode: j['comm_mode'] as String?,
        sellerPhone: j['seller_phone'] as String?,
      );
}

class OrderModel {
  const OrderModel({
    required this.id,
    required this.code,
    required this.status,
    required this.total,
    this.storeName,
    this.deliveryCode,
    this.cancellable = false,
  });

  final int id;
  final String code;
  final String status;
  final num total;
  final String? storeName;
  final String? deliveryCode;
  final bool cancellable;

  factory OrderModel.fromJson(Map<String, dynamic> j) => OrderModel(
        id: j['id'] as int,
        code: j['code'] as String,
        status: j['status'] as String,
        total: (j['total'] ?? 0) as num,
        storeName: (j['store_name'] ?? j['store']?['name']) as String?,
        deliveryCode: j['delivery_code'] as String?,
        cancellable: (j['cancellable'] as bool?) ?? false,
      );
}

class WalletEntry {
  const WalletEntry({required this.text, required this.amount, this.createdAt});
  final String text;
  final num amount;
  final String? createdAt;

  factory WalletEntry.fromJson(Map<String, dynamic> j) => WalletEntry(
        text: j['text'] as String,
        amount: j['amount'] as num,
        createdAt: j['created_at'] as String?,
      );
}

class AppNotification {
  const AppNotification({
    required this.id,
    required this.title,
    required this.body,
    this.icon,
    this.go,
    required this.read,
  });
  final int id;
  final String title;
  final String body;
  final String? icon;
  final String? go;
  final bool read;

  factory AppNotification.fromJson(Map<String, dynamic> j) => AppNotification(
        id: j['id'] as int,
        title: j['title'] as String,
        body: j['body'] as String,
        icon: j['ic'] as String?,
        go: j['go'] as String?,
        read: j['read_at'] != null,
      );
}

class AuthUser {
  const AuthUser({required this.id, required this.name, this.regionId, this.walletBalance});
  final int id;
  final String name;
  final int? regionId;
  final num? walletBalance;

  factory AuthUser.fromJson(Map<String, dynamic> j) => AuthUser(
        id: j['id'] as int,
        name: (j['name'] ?? '') as String,
        regionId: j['region_id'] as int?,
        walletBalance: j['wallet_balance'] as num?,
      );
}
