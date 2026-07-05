import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme/app_colors.dart';
import '../../data/api_repository.dart';

/// SPEC §3.10 — conversations list.
class ConversationsScreen extends ConsumerStatefulWidget {
  const ConversationsScreen({super.key});

  @override
  ConsumerState<ConversationsScreen> createState() =>
      _ConversationsScreenState();
}

class _ConversationsScreenState extends ConsumerState<ConversationsScreen> {
  bool _loading = true;
  Object? _error;
  List<Map<String, dynamic>> _items = const [];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    final api = ref.read(apiRepositoryProvider);
    try {
      final items = await api.conversations();
      if (!mounted) return;
      setState(() {
        _items = items;
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('المحادثات',
            style: Theme.of(context)
                .textTheme
                .titleLarge
                ?.copyWith(color: AppColors.maroon)),
      ),
      body: _body(),
    );
  }

  Widget _body() {
    if (_loading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_error != null) {
      return Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.error_outline, color: AppColors.danger, size: 40),
            const SizedBox(height: 12),
            Text(_error.toString(),
                textAlign: TextAlign.center,
                style: const TextStyle(color: AppColors.danger)),
            const SizedBox(height: 12),
            ElevatedButton(
                onPressed: _load, child: const Text('إعادة المحاولة')),
          ],
        ),
      );
    }
    if (_items.isEmpty) {
      return const Center(
        child: Padding(
          padding: EdgeInsets.all(32),
          child: Text('ما عندك محادثات',
              style: TextStyle(color: AppColors.mutedInk, fontSize: 15)),
        ),
      );
    }
    return RefreshIndicator(
      onRefresh: _load,
      child: ListView.separated(
        padding: const EdgeInsets.symmetric(vertical: 8),
        itemCount: _items.length,
        separatorBuilder: (_, __) =>
            const Divider(height: 1, color: AppColors.roseSoft),
        itemBuilder: (_, i) => _ConversationTile(data: _items[i]),
      ),
    );
  }
}

class _ConversationTile extends StatelessWidget {
  const _ConversationTile({required this.data});
  final Map<String, dynamic> data;

  @override
  Widget build(BuildContext context) {
    final id = data['id'];
    final storeName = (data['store_name'] ??
        data['store']?['name'] ??
        data['name'] ??
        'المصممة') as String;
    final orderCode =
        (data['order_code'] ?? data['order']?['code'] ?? data['code']) as String?;
    final lastMessage =
        (data['last_message'] ?? data['last_message_body']) as String?;
    final locked = (data['locked'] as bool?) ?? false;
    final letter = storeName.isNotEmpty ? storeName.characters.first : '؟';

    final subtitle = locked
        ? '🔒 تنفتح بعد الدفع'
        : (orderCode != null
            ? 'الطلب $orderCode'
            : (lastMessage ?? ''));

    return ListTile(
      leading: CircleAvatar(
        backgroundColor: AppColors.roseSoft,
        child: Text(letter,
            style: const TextStyle(
                color: AppColors.maroon, fontWeight: FontWeight.w700)),
      ),
      title: Text(storeName,
          style: const TextStyle(fontWeight: FontWeight.w700)),
      subtitle: subtitle.isEmpty
          ? null
          : Text(subtitle,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                  color: locked ? AppColors.warnInk : AppColors.mutedInk)),
      trailing: locked
          ? const Icon(Icons.lock_outline, color: AppColors.warnInk, size: 20)
          : const Icon(Icons.chevron_left, color: AppColors.mutedInk),
      onTap: locked || id is! int ? null : () => context.push('/chat/$id'),
    );
  }
}
