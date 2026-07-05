import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/network/api_client.dart';
import '../../core/theme/app_colors.dart';
import '../../core/utils/arabic_numbers.dart';
import '../../data/api_repository.dart';
import '../../models/models.dart';
import '../../providers/auth_provider.dart';

/// SPEC §3.7 — account with the unified wallet, withdraw & quick links.
class AccountScreen extends ConsumerStatefulWidget {
  const AccountScreen({super.key});

  @override
  ConsumerState<AccountScreen> createState() => _AccountScreenState();
}

class _AccountScreenState extends ConsumerState<AccountScreen> {
  final _withdrawCtrl = TextEditingController();

  bool _loading = true;
  bool _withdrawing = false;
  Object? _error;
  num _balance = 0;
  List<WalletEntry> _entries = const [];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  @override
  void dispose() {
    _withdrawCtrl.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final res = await ref.read(apiRepositoryProvider).wallet();
      if (!mounted) return;
      setState(() {
        _balance = res.balance;
        _entries = res.entries;
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

  void _snack(String msg) {
    if (!mounted) return;
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text(msg)));
  }

  Future<void> _withdraw() async {
    final amount = num.tryParse(_withdrawCtrl.text.trim());
    if (amount == null) {
      _snack('أدخلي مبلغًا صحيحًا');
      return;
    }
    setState(() => _withdrawing = true);
    try {
      await ref.read(apiRepositoryProvider).withdraw(amount);
      if (!mounted) return;
      _withdrawCtrl.clear();
      _snack('✓ تم تقديم طلب السحب لحسابك البنكي');
      await _load();
    } on ApiException catch (e) {
      _snack(e.message);
    } catch (_) {
      _snack('حدث خطأ، حاولي مرة ثانية');
    } finally {
      if (mounted) setState(() => _withdrawing = false);
    }
  }

  Future<void> _logout() async {
    await ref.read(authProvider.notifier).logout();
    if (!mounted) return;
    context.go('/');
  }

  @override
  Widget build(BuildContext context) {
    final user = ref.watch(authProvider).user;
    return Scaffold(
      appBar: AppBar(title: const Text('حسابي')),
      body: _buildBody(user?.name ?? ''),
    );
  }

  Widget _buildBody(String name) {
    if (_loading) {
      return const Center(child: CircularProgressIndicator());
    }
    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Text('حسابي — أهلًا $name 🌹',
              style: Theme.of(context).textTheme.titleLarge),
          const SizedBox(height: 16),
          _walletCard(),
          const SizedBox(height: 16),
          _withdrawCard(),
          const SizedBox(height: 16),
          if (_error != null)
            Text(_error.toString(),
                style: const TextStyle(color: AppColors.danger))
          else
            _ledger(),
          const SizedBox(height: 20),
          _quickLinks(),
          const SizedBox(height: 20),
          SizedBox(
            width: double.infinity,
            child: OutlinedButton.icon(
              onPressed: _logout,
              icon: const Icon(Icons.logout, color: AppColors.danger),
              label: const Text('تسجيل الخروج',
                  style: TextStyle(color: AppColors.danger)),
              style: OutlinedButton.styleFrom(
                side: const BorderSide(color: AppColors.danger),
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(99)),
                padding: const EdgeInsets.symmetric(vertical: 14),
              ),
            ),
          ),
          const SizedBox(height: 24),
        ],
      ),
    );
  }

  Widget _walletCard() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(20),
        gradient: const LinearGradient(
          colors: [AppColors.maroon, AppColors.maroonDeep],
          begin: Alignment.topRight,
          end: Alignment.bottomLeft,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('محفظتي الموحدة',
              style: TextStyle(color: AppColors.goldLight, fontSize: 13)),
          const SizedBox(height: 8),
          Text(formatSar(_balance),
              style: const TextStyle(
                  color: Colors.white,
                  fontSize: 30,
                  fontWeight: FontWeight.w800)),
        ],
      ),
    );
  }

  Widget _withdrawCard() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppColors.roseSoft),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('سحب من الرصيد',
              style: TextStyle(fontWeight: FontWeight.w700)),
          const SizedBox(height: 10),
          Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _withdrawCtrl,
                  keyboardType:
                      const TextInputType.numberWithOptions(decimal: true),
                  decoration: const InputDecoration(
                    hintText: 'الحد الأدنى ١٠٠ ر.س',
                  ),
                ),
              ),
              const SizedBox(width: 8),
              ElevatedButton(
                onPressed: _withdrawing ? null : _withdraw,
                child: _withdrawing
                    ? const SizedBox(
                        height: 18,
                        width: 18,
                        child: CircularProgressIndicator(
                            strokeWidth: 2, color: Colors.white),
                      )
                    : const Text('سحب لحسابي البنكي'),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _ledger() {
    if (_entries.isEmpty) {
      return const Padding(
        padding: EdgeInsets.symmetric(vertical: 16),
        child: Text('لا توجد حركات بعد',
            style: TextStyle(color: AppColors.mutedInk)),
      );
    }
    return Container(
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppColors.roseSoft),
      ),
      child: Column(
        children: [
          for (var i = 0; i < _entries.length; i++) ...[
            if (i > 0) const Divider(height: 1),
            _ledgerRow(_entries[i]),
          ],
        ],
      ),
    );
  }

  Widget _ledgerRow(WalletEntry entry) {
    final positive = entry.amount > 0;
    final sign = positive ? '+' : '−';
    final color = positive ? AppColors.green : AppColors.danger;
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      child: Row(
        children: [
          Expanded(
            child: Text(entry.text, style: const TextStyle(fontSize: 13.5)),
          ),
          const SizedBox(width: 8),
          Text('$sign ${formatSar(entry.amount.abs())}',
              style: TextStyle(fontWeight: FontWeight.w700, color: color)),
        ],
      ),
    );
  }

  Widget _quickLinks() {
    return Wrap(
      spacing: 10,
      runSpacing: 10,
      children: [
        _quickLink('📦 طلباتي', () => context.push('/orders')),
        _quickLink('💬 محادثاتي', () => _snack('قريبًا')),
        _quickLink('♥ مفضلتي', () => _snack('قريبًا')),
        _quickLink('🪄 طلباتي الذكية', () => context.push('/smart-request')),
      ],
    );
  }

  Widget _quickLink(String label, VoidCallback onTap) {
    return OutlinedButton(
      onPressed: onTap,
      style: OutlinedButton.styleFrom(
        foregroundColor: AppColors.maroon,
        side: const BorderSide(color: AppColors.roseSoft),
        shape:
            RoundedRectangleBorder(borderRadius: BorderRadius.circular(99)),
      ),
      child: Text(label),
    );
  }
}
