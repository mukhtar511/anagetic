import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/theme/app_colors.dart';
import '../../core/utils/arabic_numbers.dart';
import '../../data/api_repository.dart';

/// SPEC §3.10 — chat thread tied to an order.
class ChatScreen extends ConsumerStatefulWidget {
  const ChatScreen({super.key, required this.conversationId});
  final int conversationId;

  @override
  ConsumerState<ChatScreen> createState() => _ChatScreenState();
}

class _ChatScreenState extends ConsumerState<ChatScreen> {
  final _inputCtrl = TextEditingController();
  final _scrollCtrl = ScrollController();

  bool _loading = true;
  bool _sending = false;
  Object? _error;
  List<Map<String, dynamic>> _messages = const [];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  @override
  void dispose() {
    _inputCtrl.dispose();
    _scrollCtrl.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    final api = ref.read(apiRepositoryProvider);
    try {
      final messages = await api.messages(widget.conversationId);
      if (!mounted) return;
      setState(() {
        _messages = messages;
        _loading = false;
      });
      _scrollToBottom();
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e;
        _loading = false;
      });
    }
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollCtrl.hasClients) {
        _scrollCtrl.jumpTo(_scrollCtrl.position.maxScrollExtent);
      }
    });
  }

  Future<void> _send() async {
    final text = _inputCtrl.text.trim();
    if (text.isEmpty || _sending) return;
    setState(() => _sending = true);
    final api = ref.read(apiRepositoryProvider);
    try {
      await api.sendMessage(widget.conversationId, text);
      _inputCtrl.clear();
      await _load();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(e.toString())));
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  Future<void> _acceptAddon(int offerId) async {
    final api = ref.read(apiRepositoryProvider);
    try {
      await api.acceptAddonOffer(offerId);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('✓ وافقتِ على الإضافة')),
      );
      await _load();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(e.toString())));
    }
  }

  Future<void> _rejectAddon(int offerId) async {
    final api = ref.read(apiRepositoryProvider);
    try {
      await api.rejectAddonOffer(offerId);
      await _load();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(e.toString())));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('المحادثة',
            style: Theme.of(context)
                .textTheme
                .titleLarge
                ?.copyWith(color: AppColors.maroon)),
      ),
      body: Column(
        children: [
          Expanded(child: _body()),
          _inputRow(),
        ],
      ),
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
    return ListView(
      controller: _scrollCtrl,
      padding: const EdgeInsets.all(16),
      children: [
        const _SystemNote(
          '🔒 عروض الإضافات تنضاف للطلب رسميًا داخل المنصة — لا تحوّلين أي مبلغ خارج أناقتك',
        ),
        const SizedBox(height: 12),
        if (_messages.isEmpty)
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 32),
            child: Center(
              child: Text('ابدئي المحادثة',
                  style: TextStyle(color: AppColors.mutedInk)),
            ),
          ),
        for (final m in _messages) _message(m),
      ],
    );
  }

  Widget _message(Map<String, dynamic> m) {
    final type = (m['type'] ?? 'text') as String;
    if (type == 'system') {
      return _SystemNote((m['body'] ?? '') as String);
    }
    if (type == 'addon_offer') {
      final offer = (m['offer'] as Map?)?.cast<String, dynamic>();
      if (offer != null) {
        return _AddonOfferCard(
          offer: offer,
          onAccept: () {
            final id = offer['id'];
            if (id is int) _acceptAddon(id);
          },
          onReject: () {
            final id = offer['id'];
            if (id is int) _rejectAddon(id);
          },
        );
      }
    }
    return _Bubble(body: (m['body'] ?? '') as String, mine: _isMine(m));
  }

  bool _isMine(Map<String, dynamic> m) {
    final v = m['is_mine'] ?? m['mine'];
    if (v is bool) return v;
    final sender = m['sender'];
    if (sender is String) return sender == 'me' || sender == 'buyer';
    return false;
  }

  Widget _inputRow() {
    return SafeArea(
      top: false,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        decoration: const BoxDecoration(
          color: AppColors.white,
          border: Border(top: BorderSide(color: AppColors.roseSoft)),
        ),
        child: Row(
          children: [
            Expanded(
              child: TextField(
                controller: _inputCtrl,
                textInputAction: TextInputAction.send,
                onSubmitted: (_) => _send(),
                decoration: const InputDecoration(
                  hintText: 'اكتبي رسالتك...',
                  border: OutlineInputBorder(),
                  isDense: true,
                ),
              ),
            ),
            const SizedBox(width: 8),
            IconButton.filled(
              style: IconButton.styleFrom(backgroundColor: AppColors.maroon),
              icon: _sending
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(
                          strokeWidth: 2, color: Colors.white),
                    )
                  : const Icon(Icons.send, color: Colors.white),
              onPressed: _sending ? null : _send,
            ),
          ],
        ),
      ),
    );
  }
}

class _Bubble extends StatelessWidget {
  const _Bubble({required this.body, required this.mine});
  final String body;
  final bool mine;

  @override
  Widget build(BuildContext context) {
    return Align(
      alignment: mine ? Alignment.centerLeft : Alignment.centerRight,
      child: Container(
        margin: const EdgeInsets.symmetric(vertical: 4),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
        constraints: BoxConstraints(
            maxWidth: MediaQuery.of(context).size.width * 0.72),
        decoration: BoxDecoration(
          color: mine ? AppColors.maroon : AppColors.roseSoft,
          borderRadius: BorderRadius.circular(16),
        ),
        child: Text(body,
            style: TextStyle(
                color: mine ? Colors.white : AppColors.ink, height: 1.4)),
      ),
    );
  }
}

class _SystemNote extends StatelessWidget {
  const _SystemNote(this.text);
  final String text;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Container(
        margin: const EdgeInsets.symmetric(vertical: 6),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        decoration: BoxDecoration(
          color: AppColors.warnBg,
          borderRadius: BorderRadius.circular(99),
        ),
        child: Text(text,
            textAlign: TextAlign.center,
            style: const TextStyle(fontSize: 11.5, color: AppColors.warnInk)),
      ),
    );
  }
}

class _AddonOfferCard extends StatelessWidget {
  const _AddonOfferCard({
    required this.offer,
    required this.onAccept,
    required this.onReject,
  });

  final Map<String, dynamic> offer;
  final VoidCallback onAccept;
  final VoidCallback onReject;

  @override
  Widget build(BuildContext context) {
    final name = (offer['name'] ?? 'إضافة') as String;
    final price = (offer['price'] ?? 0) as num;
    final status = (offer['status'] ?? 'pending') as String;
    final decided = status == 'accepted' || status == 'rejected';

    return Container(
      margin: const EdgeInsets.symmetric(vertical: 8),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.gold, width: 1.5),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('💎 عرض إضافة رسمي',
              style: TextStyle(
                  fontWeight: FontWeight.w700, color: AppColors.gold)),
          const SizedBox(height: 8),
          Text(name, style: const TextStyle(fontWeight: FontWeight.w600)),
          const SizedBox(height: 4),
          Text(formatSar(price),
              style: const TextStyle(
                  fontWeight: FontWeight.w700, color: AppColors.maroon)),
          const SizedBox(height: 12),
          if (status == 'accepted')
            const Text('✓ وافقتِ على الإضافة',
                style: TextStyle(
                    color: AppColors.green, fontWeight: FontWeight.w600))
          else if (status == 'rejected')
            const Text('✕ رُفض العرض',
                style: TextStyle(color: AppColors.danger))
          else
            Row(
              children: [
                Expanded(
                  child: ElevatedButton(
                    onPressed: decided ? null : onAccept,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.maroon,
                      foregroundColor: Colors.white,
                    ),
                    child: const Text('موافقة — تنضاف للطلب'),
                  ),
                ),
                const SizedBox(width: 10),
                OutlinedButton(
                  onPressed: decided ? null : onReject,
                  child: const Text('رفض'),
                ),
              ],
            ),
        ],
      ),
    );
  }
}
