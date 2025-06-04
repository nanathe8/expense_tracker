import 'package:flutter/material.dart';

class TransactionDetailSheet extends StatefulWidget {
  final String date;
  final String description;
  final double amount;
  final String type;
  final VoidCallback onDelete;
  final Function(String, double, String) onUpdate;

  const TransactionDetailSheet({
    super.key,
    required this.date,
    required this.description,
    required this.amount,
    required this.type,
    required this.onDelete,
    required this.onUpdate,
  });

  @override
  State<TransactionDetailSheet> createState() => _TransactionDetailSheetState();
}

class _TransactionDetailSheetState extends State<TransactionDetailSheet> {
  late TextEditingController _descriptionController;
  late TextEditingController _amountController;
  late String _selectedType;

  @override
  void initState() {
    super.initState();
    _descriptionController = TextEditingController(text: widget.description);
    _amountController = TextEditingController(text: widget.amount.toStringAsFixed(2));
    _selectedType = widget.type;
  }

  @override
  void dispose() {
    _descriptionController.dispose();
    _amountController.dispose();
    super.dispose();
  }

  void _handleUpdate() {
    final updatedDescription = _descriptionController.text.trim();
    final updatedAmount = double.tryParse(_amountController.text) ?? 0.0;

    if (updatedDescription.isNotEmpty && updatedAmount > 0) {
      widget.onUpdate(updatedDescription, updatedAmount, _selectedType);
      Navigator.pop(context);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(
        top: 24,
        left: 16,
        right: 16,
        bottom: 32,
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            'Transaction Details',
            style: Theme.of(context).textTheme.titleLarge?.copyWith(
              fontWeight: FontWeight.bold,
              color: Colors.deepPurple,
            ),
          ),
          const SizedBox(height: 20),
          TextField(
            controller: _descriptionController,
            decoration: const InputDecoration(
              labelText: 'Description',
              border: OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 15),
          TextField(
            controller: _amountController,
            keyboardType: TextInputType.number,
            decoration: const InputDecoration(
              labelText: 'Amount (RM)',
              border: OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 15),
          DropdownButtonFormField<String>(
            value: _selectedType,
            decoration: const InputDecoration(
              labelText: 'Type',
              border: OutlineInputBorder(),
            ),
            items: const [
              DropdownMenuItem(value: 'Income', child: Text('Income')),
              DropdownMenuItem(value: 'Expense', child: Text('Expense')),
            ],
            onChanged: (value) {
              if (value != null) {
                setState(() => _selectedType = value);
              }
            },
          ),
          const SizedBox(height: 25),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              ElevatedButton.icon(
                onPressed: widget.onDelete,
                icon: const Icon(Icons.delete),
                label: const Text('Delete'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.redAccent,
                ),
              ),
              ElevatedButton.icon(
                onPressed: _handleUpdate,
                icon: const Icon(Icons.save),
                label: const Text('Update'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.deepPurple,
                ),
              ),
            ],
          )
        ],
      ),
    );
  }
}
