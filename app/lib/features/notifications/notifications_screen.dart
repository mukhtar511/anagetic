import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme/app_colors.dart';
import '../../data/api_repository.dart';
import '../../models/models.dart';

/// SPEC §3.12 — notifications centre.
class NotificationsScreen extends ConsumerStatefulWidget {
  const NotificationsScreen({super.key});

  @override
  ConsumerState<NotificationsScreen> createState() =>
      _NotificationsScreenState();
}

class _NotificationsScreenState extends ConsumerState<NotificationsScreen> {
  bool _loading = true;
  Object? _error;
  List<AppNotification> _items = const [];

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final res = await ref.read(apiRepositoryProvider).notifications();
      if (!mounted) return;
      setState(() {
        _items = res.items;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e;
        _loading = false;
      });
    }
  }

  Future<void> _markAll() async {
    try {
      await ref.read(apiRepositoryProvider).readAllNotifications();
    } catch (_) {}
    if (!mounted) return;
    await _load();
  }

  void _onTap(AppNotification n) {
    switch (n.go) {
      case 'buyerDash':
        context.push('/account');
        break;
      case 'ordersPage':
        context.push('/orders');
        break;
      case 'smartReqPage':
        context.push('/smart-request');
        break;
      default:
        break;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('🔔 إشعاراتي'),
        actions: [
          TextButton(
            onPressed: _markAll,
            child: const Text('تحديد الكل كمقروء'),
          ),
        ],
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_loading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_error != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.error_outline,
                  color: AppColors.danger, size: 40),
              const SizedBox(height: 12),
              Text(_error.toString(),
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: AppColors.danger)),
              const SizedBox(height: 12),
              ElevatedButton(
                  onPressed: _load, child: const Text('إعادة المحاولة')),
            ],
          ),
        ),
      );
    }
    if (_items.isEmpty) {
      return const Center(
        child: Text('ما عندك إشعارات حاليًا',
            style: TextStyle(color: AppColors.mutedInk)),
      );
    }
    return RefreshIndicator(
      onRefresh: _load,
      child: ListView.separated(
        padding: const EdgeInsets.all(12),
        itemCount: _items.length,
        separatorBuilder: (_, __) => const SizedBox(height: 8),
        itemBuilder: (_, i) => _tile(_items[i]),
      ),
    );
  }

  Widget _tile(AppNotification n) {
    return Material(
      color: n.read ? AppColors.white : AppColors.roseBg,
      borderRadius: BorderRadius.circular(14),
      child: InkWell(
        borderRadius: BorderRadius.circular(14),
        onTap: () => _onTap(n),
        child: Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(14),
            border: Border.all(
              color: n.read ? AppColors.roseSoft : AppColors.maroon,
              width: n.read ? 1 : 1.4,
            ),
          ),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(n.icon ?? '🔔', style: const TextStyle(fontSize: 22)),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(n.title,
                        style: TextStyle(
                          fontWeight:
                              n.read ? FontWeight.w600 : FontWeight.w700,
                          color: AppColors.ink,
                        )),
                    const SizedBox(height: 4),
                    Text(n.body,
                        style: const TextStyle(
                            fontSize: 13, color: AppColors.mutedInk)),
                  ],
                ),
              ),
              if (!n.read)
                Container(
                  margin: const EdgeInsets.only(top: 4),
                  width: 9,
                  height: 9,
                  decoration: const BoxDecoration(
                    color: AppColors.maroon,
                    shape: BoxShape.circle,
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }
}
