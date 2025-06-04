import 'package:flutter/material.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'base_screen.dart'; // Import your base layout
import 'config.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:io';



Future<void> storeSessionId(String sessionId) async {
  SharedPreferences prefs = await SharedPreferences.getInstance();
  await prefs.setString('PHPSESSID', sessionId);
}

Future<String?> getSessionId() async {
  SharedPreferences prefs = await SharedPreferences.getInstance();
  return prefs.getString('PHPSESSID');
}

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  _HomeScreenState createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  Map<String, dynamic>? data;
  bool isLoading = true;
  bool isIncomeVisible = false;
  bool isExpensesVisible = false;
  double totalIncome = 0.0;
  double totalExpenses = 0.0;
  double remainingBalance = 0.0;
  List<dynamic> transactions = [];
  String errorMessage = '';
  int? budgetId;

  Future<void> fetchData() async {
    if (!mounted) return;

    setState(() {
      isLoading = true;
      errorMessage = '';
    });

    String? sessionId = await getSessionId();

    try {
      final response = await http.post(
        Uri.parse('${CONFIG.SERVER}/mainDashboard.php'),
        headers: {
          'Cookie': 'PHPSESSID=$sessionId',
        },
      );

      print('Status Code: ${response.statusCode}');
      print('Response Body: ${response.body}');

      if (response.statusCode == 200) {
        final jsonData = json.decode(response.body);

        setState(() {
          data = jsonData;
          totalIncome = (jsonData['total_income'] is int)
              ? (jsonData['total_income'] as int).toDouble()
              : (jsonData['total_income'] ?? 0.0);

          totalExpenses = (jsonData['total_expenses'] is int)
              ? (jsonData['total_expenses'] as int).toDouble()
              : (jsonData['total_expenses'] ?? 0.0);

          remainingBalance = totalIncome - totalExpenses;
          transactions = jsonData['transactions'] ?? [];
          budgetId = jsonData['budgetID'];
          isLoading = false;
          errorMessage = '';

          print('total_income type: ${jsonData['total_income'].runtimeType}');
          print('total_expenses type: ${jsonData['total_expenses'].runtimeType}');

        });

        print('Budget ID: $budgetId');
      } else {
        setState(() {
          errorMessage = 'Failed to load data';
          isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        errorMessage = 'Error fetching data: $e';
        isLoading = false;
      });
      print('Error: $e');
    }
  }

  @override
  void initState() {
    super.initState();
    fetchData();
  }

  void toggleIncomeVisibility() {
    setState(() => isIncomeVisible = !isIncomeVisible);
  }

  void toggleExpensesVisibility() {
    setState(() => isExpensesVisible = !isExpensesVisible);
  }

  @override
  Widget build(BuildContext context) {
    return BaseScreen(
      title: 'Home',
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: SingleChildScrollView(
          child: Column(
            children: <Widget>[
              const SizedBox(height: 20),
              const Text(
                'Remaining Balance',
                style: TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                  color: Colors.black,
                ),
              ),
              const SizedBox(height: 30),
              Stack(
                alignment: Alignment.center,
                children: [
                  CircularProgressIndicator(
                    value: totalIncome != 0 ? remainingBalance / totalIncome : 0,
                    strokeWidth: 8,
                    backgroundColor: Colors.grey.shade700,
                    valueColor: const AlwaysStoppedAnimation(Colors.orange),
                  ),
                  Text(
                    'RM ${remainingBalance.toStringAsFixed(2)}',
                    style: const TextStyle(
                      fontSize: 30,
                      fontWeight: FontWeight.bold,
                      color: Colors.black,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 30),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                children: <Widget>[
                  InfoCard(
                    title: 'Total Income',
                    value: isIncomeVisible
                        ? 'RM ${totalIncome.toStringAsFixed(2)}'
                        : '****',
                    color: Colors.green,
                    onToggleVisibility: toggleIncomeVisibility,
                  ),
                  InfoCard(
                    title: 'Total Expenses',
                    value: isExpensesVisible
                        ? 'RM ${totalExpenses.toStringAsFixed(2)}'
                        : '****',
                    color: Colors.red,
                    onToggleVisibility: toggleExpensesVisibility,
                  ),
                ],
              ),
              const SizedBox(height: 30),
              const Text(
                'Recent Transactions:',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: Colors.black,
                ),
              ),
              const SizedBox(height: 10),
              isLoading
                  ? const Center(child: CircularProgressIndicator())
                  : errorMessage.isNotEmpty
                  ? Center(
                child: Text(
                  errorMessage,
                  style:
                  const TextStyle(color: Colors.red, fontSize: 16),
                ),
              )
                  : transactions.isEmpty
                  ? const Text('No transactions found.')
                  : Column(
                children: transactions.take(3).map((transaction) {
                  return TransactionItem(
                    date: transaction['date'] ?? 'Unknown date',
                    description:
                    transaction['description'] ?? 'No description',
                    amount: double.tryParse(
                        transaction['amount'].toString()) ??
                        0.0,
                    type: transaction['type'] ?? 'Expense',
                    id: transaction['id'] ?? '',
                    onRefresh: () => fetchData(),
                    categoryName: transaction['categoryName'] ?? 'N/A',
                    receiptImage: transaction['receiptImage'] ?? '',
                    budgetName: transaction['budgetName'] ?? 'N/A',
                  );
                }).toList(),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class InfoCard extends StatelessWidget {
  final String title;
  final String value;
  final Color color;
  final VoidCallback onToggleVisibility;

  const InfoCard({
    super.key,
    required this.title,
    required this.value,
    required this.color,
    required this.onToggleVisibility,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      color: color,
      elevation: 8,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
      child: InkWell(
        onTap: onToggleVisibility,
        child: Container(
          padding: const EdgeInsets.all(20),
          width: 140,
          child: Column(
            children: <Widget>[
              Text(
                title,
                style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: Colors.black,
                ),
              ),
              const SizedBox(height: 10),
              Text(
                value,
                style: const TextStyle(
                  fontSize: 22,
                  fontWeight: FontWeight.bold,
                  color: Colors.black,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class TransactionItem extends StatelessWidget {
  final String id;
  final String date;
  final String description;
  final double amount;
  final String type;
  final VoidCallback onRefresh;
  final String categoryName;
  final String receiptImage;
  final String budgetName;

  const TransactionItem({
    super.key,
    required this.id,
    required this.date,
    required this.description,
    required this.amount,
    required this.type,
    required this.onRefresh,
    required this.categoryName,
    required this.receiptImage,
    required this.budgetName,
  });

  Future<void> updateTransaction(
      BuildContext context, String updatedDesc, double updatedAmount, String updatedType) async {
    final response = await http.post(
      Uri.parse('${CONFIG.SERVER}/getTransaction.php'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({
        'action': 'update',
        'id': id,
        'description': updatedDesc,
        'amount': updatedAmount,
        'type': updatedType,
      }),
    );

    final data = jsonDecode(response.body);
    if (data['success']) {
      Navigator.pop(context);
      onRefresh(); // To reload transaction list
    } else {
      ScaffoldMessenger.of(context)
          .showSnackBar(const SnackBar(content: Text('Update failed')));
    }
  }

  Future<void> deleteTransaction(BuildContext context) async {
    final response = await http.post(
      Uri.parse('${CONFIG.SERVER}/getTransaction.php'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({
        'action': 'delete',
        'id': id,
      }),
    );

    final data = jsonDecode(response.body);
    if (data['success']) {
      Navigator.pop(context);
      onRefresh();
    } else {
      ScaffoldMessenger.of(context)
          .showSnackBar(const SnackBar(content: Text('Delete failed')));
    }
  }

  @override
  Widget build(BuildContext context) {
    final bool isIncome = type.toLowerCase() == 'income';
    final Color baseColor = isIncome ? Colors.green.shade700 : Colors.red.shade700;

    return Card(
      margin: const EdgeInsets.symmetric(vertical: 6),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
      elevation: 3,
      child: ListTile(
        contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
        onTap: () {
          showModalBottomSheet(
            context: context,
            isScrollControlled: true,
            shape: const RoundedRectangleBorder(
              borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
            ),
            builder: (_) => TransactionDetailSheet(
              date: date,
              description: description,
              amount: amount,
              type: type,
              categoryName: categoryName,
              receiptImage: receiptImage,
              budgetName: budgetName,
              onDelete: () => deleteTransaction(context),
              onUpdate: (newDesc, newAmt, newType) =>
                  updateTransaction(context, newDesc, newAmt, newType),
            ),
          );
        },
        leading: CircleAvatar(
          backgroundColor: baseColor.withOpacity(0.2),
          child: Icon(
            isIncome ? Icons.arrow_upward : Icons.arrow_downward,
            color: baseColor,
          ),
        ),
        title: Text(
          '$description ($type)',
          style: TextStyle(
            color: Colors.deepPurple.shade900,
            fontWeight: FontWeight.w600,
          ),
        ),
        subtitle: Text('Date: $date'),
        trailing: Text(
          '${isIncome ? '+' : '-'} RM ${amount.toStringAsFixed(2)}',
          style: TextStyle(
            color: baseColor,
            fontWeight: FontWeight.bold,
          ),
        ),
      ),
    );
  }
}

class TransactionDetailSheet extends StatefulWidget {
  final String date;
  final String description;
  final double amount;
  final String type;
  final String categoryName;
  final String receiptImage; // URL or base64 string
  final String budgetName;
  final VoidCallback onDelete;
  final Function(String, double, String) onUpdate;

  const TransactionDetailSheet({
    Key? key,
    required this.date,
    required this.description,
    required this.amount,
    required this.type,
    required this.categoryName,
    required this.receiptImage,
    required this.budgetName,
    required this.onDelete,
    required this.onUpdate,
  }) : super(key: key);

  @override
  _TransactionDetailSheetState createState() => _TransactionDetailSheetState();
}

class _TransactionDetailSheetState extends State<TransactionDetailSheet> {
  bool isEditing = false;
  late TextEditingController _descController;
  late TextEditingController _amountController;
  late String _selectedType;

  @override
  void initState() {
    super.initState();
    _descController = TextEditingController(text: widget.description);
    _amountController =
        TextEditingController(text: widget.amount.toStringAsFixed(2));
    _selectedType = widget.type;
  }

  @override
  void dispose() {
    _descController.dispose();
    _amountController.dispose();
    super.dispose();
  }

  void _saveChanges() {
    final updatedDesc = _descController.text.trim();
    final updatedAmount = double.tryParse(_amountController.text.trim()) ?? 0.0;
    if (updatedDesc.isEmpty || updatedAmount <= 0) {
      ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Please enter valid details')));
      return;
    }

    widget.onUpdate(updatedDesc, updatedAmount, _selectedType);
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding:
      EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
      child: Container(
        padding: const EdgeInsets.all(20),
        height: isEditing ? 420 : 350,
        child: SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 50,
                  height: 6,
                  margin: const EdgeInsets.only(bottom: 20),
                  decoration: BoxDecoration(
                    color: Colors.grey[400],
                    borderRadius: BorderRadius.circular(3),
                  ),
                ),
              ),
              if (!isEditing) ...[
                Text('Date: ${widget.date}', style: const TextStyle(fontSize: 16)),
                const SizedBox(height: 10),
                Text('Description: ${widget.description}',
                    style: const TextStyle(fontSize: 16)),
                const SizedBox(height: 10),
                Text('Amount: RM ${widget.amount.toStringAsFixed(2)}',
                    style: const TextStyle(fontSize: 16)),
                const SizedBox(height: 10),
                Text('Type: ${widget.type}', style: const TextStyle(fontSize: 16)),
                const SizedBox(height: 10),
                Text('Category: ${widget.categoryName}',
                    style: const TextStyle(fontSize: 16)),
                const SizedBox(height: 10),
                Text('Budget: ${widget.budgetName}',
                    style: const TextStyle(fontSize: 16)),
                const SizedBox(height: 10),
                widget.receiptImage.isNotEmpty
                    ? Image.file(
                  File(widget.receiptImage),
                  height: 150,
                  fit: BoxFit.cover,
                )
                    : const SizedBox(),
              ] else ...[
                TextField(
                  controller: _descController,
                  decoration:
                  const InputDecoration(labelText: 'Description'),
                ),
                TextField(
                  controller: _amountController,
                  keyboardType: TextInputType.numberWithOptions(decimal: true),
                  decoration: const InputDecoration(labelText: 'Amount'),
                ),
                const SizedBox(height: 10),
                DropdownButtonFormField<String>(
                  value: _selectedType,
                  items: const [
                    DropdownMenuItem(value: 'Income', child: Text('Income')),
                    DropdownMenuItem(value: 'Expense', child: Text('Expense')),
                  ],
                  onChanged: (value) {
                    if (value != null) {
                      setState(() => _selectedType = value);
                    }
                  },
                  decoration: const InputDecoration(labelText: 'Type'),
                ),
              ],
              const SizedBox(height: 20),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceAround,
                children: [
                  ElevatedButton.icon(
                    icon: Icon(isEditing ? Icons.cancel : Icons.edit),
                    label: Text(isEditing ? 'Cancel' : 'Edit'),
                    onPressed: () {
                      setState(() {
                        if (isEditing) {
                          // Cancel edits, revert fields
                          _descController.text = widget.description;
                          _amountController.text =
                              widget.amount.toStringAsFixed(2);
                          _selectedType = widget.type;
                        }
                        isEditing = !isEditing;
                      });
                    },
                  ),
                  if (isEditing)
                    ElevatedButton.icon(
                      icon: const Icon(Icons.save),
                      label: const Text('Save'),
                      onPressed: _saveChanges,
                    ),
                  ElevatedButton.icon(
                    icon: const Icon(Icons.delete),
                    label: const Text('Delete'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.red,
                    ),
                    onPressed: () async {
                      final confirm = await showDialog<bool>(
                        context: context,
                        builder: (_) => AlertDialog(
                          title: const Text('Confirm Delete'),
                          content: const Text(
                              'Are you sure you want to delete this transaction?'),
                          actions: [
                            TextButton(
                                onPressed: () => Navigator.pop(context, false),
                                child: const Text('Cancel')),
                            TextButton(
                                onPressed: () => Navigator.pop(context, true),
                                child: const Text('Delete')),
                          ],
                        ),
                      );
                      if (confirm == true) {
                        widget.onDelete();
                      }
                    },
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}
