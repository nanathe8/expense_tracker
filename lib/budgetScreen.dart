import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import 'base_screen.dart';
import 'addBudgetScreen.dart';
import 'config.dart';

class BudgetListPage extends StatefulWidget {
  const BudgetListPage({Key? key}) : super(key: key);

  @override
  _BudgetListPageState createState() => _BudgetListPageState();
}

class _BudgetListPageState extends State<BudgetListPage> {
  bool isLoading = true;
  List budgets = [];
  bool showDeleted = false;


  @override
  void initState() {
    super.initState();
    fetchBudgets();
  }

  Future<String?> getSessionId() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('PHPSESSID');
  }

  String formatDate(String? rawDate) {
    if (rawDate == null || rawDate.isEmpty) return '-';
    return rawDate.split('T').first;
  }

  Future<void> fetchBudgets() async {
    setState(() => isLoading = true);
    try {
      final phpSessionId = await getSessionId();
      final response = await http.get(
        Uri.parse('${CONFIG.SERVER}/viewBudget.php?showDeleted=$showDeleted'),
        headers: {'Cookie': 'PHPSESSID=$phpSessionId'},
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        setState(() {
          // Make sure your backend filters out deleted budgets (deleted_at IS NULL)
          budgets = data['budgets'] ?? [];
          isLoading = false;
        });
      } else {
        throw Exception('Failed to load budgets');
      }
    } catch (e) {
      setState(() => isLoading = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error fetching budgets: $e')),
      );
    }
  }

  Future<bool> updateBudget(
      int budgetID,
      String budgetName,
      String startDate,
      String endDate,
      String budgetAmount,
      ) async {
    final url = '${CONFIG.SERVER}/editBudget.php';
    try {
      final phpSessionId = await getSessionId();
      final response = await http.post(
        Uri.parse(url),
        headers: {
          'Content-Type': 'application/json',
          'Cookie': 'PHPSESSID=$phpSessionId',
        },
        body: json.encode({
          'budgetID': budgetID,
          'budgetName': budgetName,
          'budgetAmount': budgetAmount,
          'startDate': startDate,
          'endDate': endDate,
        }),
      );

      final data = json.decode(response.body);
      if (data['status'] == 'success') {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Budget updated successfully')),
        );
        fetchBudgets();
        return true;
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(data['message'] ?? 'Update failed')),
        );
        return false;
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error updating budget: $e')),
      );
      return false;
    }
  }

  Future<void> deleteBudget(int budgetID) async {
    final url = '${CONFIG.SERVER}/deleteBudget.php';
    try {
      final phpSessionId = await getSessionId();
      final response = await http.post(
        Uri.parse(url),
        headers: {
          'Content-Type': 'application/json',
          'Cookie': 'PHPSESSID=$phpSessionId',
        },
        body: json.encode({'budgetID': budgetID}),
      );

      final data = json.decode(response.body);
      if (data['status'] == 'success') {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Budget deleted successfully')),
        );
        fetchBudgets();
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(data['message'] ?? 'Delete failed')),
        );
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error deleting budget: $e')),
      );
    }
  }

  Future<void> recoverBudget(int budgetID) async {
    final url = '${CONFIG.SERVER}/recoverBudget.php';
    try {
      final phpSessionId = await getSessionId();
      final response = await http.post(
        Uri.parse(url),
        headers: {
          'Content-Type': 'application/json',
          'Cookie': 'PHPSESSID=$phpSessionId',
        },
        body: json.encode({'budgetID': budgetID}),
      );

      final data = json.decode(response.body);
      if (data['status'] == 'success') {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Budget recovered successfully')),
        );
        fetchBudgets(); // reload list
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(data['message'] ?? 'Recovery failed')),
        );
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error recovering budget: $e')),
      );
    }
  }


  void showEditDialog(Map budget) {
    final TextEditingController nameController =
    TextEditingController(text: budget['budgetName']);
    final TextEditingController amountController =
    TextEditingController(text: budget['budgetAmount']?.toString() ?? '');

    DateTime? startDate = DateTime.tryParse(budget['startDate'] ?? '');
    DateTime? endDate = DateTime.tryParse(budget['endDate'] ?? '');

    showDialog(
      context: context,
      builder: (context) {
        return StatefulBuilder(builder: (context, setStateDialog) {
          return AlertDialog(
            title: const Text('Edit Budget'),
            content: SingleChildScrollView(
              child: Column(
                children: [
                  TextField(
                    controller: nameController,
                    decoration: const InputDecoration(labelText: 'Budget Name'),
                  ),
                  const SizedBox(height: 10),
                  TextField(
                    controller: amountController,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(labelText: 'Budget Amount'),
                  ),
                  const SizedBox(height: 10),
                  InkWell(
                    onTap: () async {
                      final picked = await showDatePicker(
                        context: context,
                        initialDate: startDate ?? DateTime.now(),
                        firstDate: DateTime(2020),
                        lastDate: DateTime(2101),
                      );
                      if (picked != null) {
                        setStateDialog(() {
                          startDate = picked;
                        });
                      }
                    },
                    child: InputDecorator(
                      decoration: const InputDecoration(
                        labelText: 'Start Date',
                        border: OutlineInputBorder(),
                      ),
                      child: Text(
                        startDate == null
                            ? 'Select Start Date'
                            : startDate!.toLocal().toString().split(' ')[0],
                      ),
                    ),
                  ),
                  const SizedBox(height: 10),
                  InkWell(
                    onTap: () async {
                      final picked = await showDatePicker(
                        context: context,
                        initialDate: endDate ?? DateTime.now(),
                        firstDate: DateTime(2020),
                        lastDate: DateTime(2101),
                      );
                      if (picked != null) {
                        setStateDialog(() {
                          endDate = picked;
                        });
                      }
                    },
                    child: InputDecorator(
                      decoration: const InputDecoration(
                        labelText: 'End Date',
                        border: OutlineInputBorder(),
                      ),
                      child: Text(
                        endDate == null
                            ? 'Select End Date'
                            : endDate!.toLocal().toString().split(' ')[0],
                      ),
                    ),
                  ),
                ],
              ),
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context),
                child: const Text('Cancel'),
              ),
              ElevatedButton(
                onPressed: () async {
                  if (nameController.text.isEmpty ||
                      amountController.text.isEmpty ||
                      startDate == null ||
                      endDate == null) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('Please fill all fields')),
                    );
                    return;
                  }
                  if (startDate!.isAfter(endDate!)) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(
                          content: Text('Start date cannot be after end date')),
                    );
                    return;
                  }

                  bool success = await updateBudget(
                    budget['budgetID'],
                    nameController.text,
                    startDate!.toIso8601String(),
                    endDate!.toIso8601String(),
                    amountController.text,
                  );

                  if (success) Navigator.pop(context);
                },
                child: const Text('Save'),
              ),
            ],
          );
        });
      },
    );
  }

  void confirmDelete(int budgetID) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Confirm Delete'),
        content: const Text('Are you sure you want to delete this budget?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () {
              deleteBudget(budgetID);
              Navigator.pop(context);
            },
            child: const Text('Delete'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: BaseScreen(
        title: 'Budgets',
        actions: [
          IconButton(
            icon: Icon(
              showDeleted ? Icons.visibility_off : Icons.visibility,
              color: Colors.black,  // explicitly set color here
            ),
            tooltip: showDeleted ? 'Hide Deleted' : 'Show Deleted',
            onPressed: () {
              setState(() {
                showDeleted = !showDeleted;
                fetchBudgets(); // refresh list
              });
            },
          ),
        ],
        body: isLoading
            ? const Center(child: CircularProgressIndicator())
            : budgets.isEmpty
            ? const Center(child: Text('No budgets found.'))
            : ListView.builder(
          itemCount: budgets.length,
          itemBuilder: (context, index) {
            final budget = budgets[index];
            return Card(
              margin: const EdgeInsets.all(8),
              child: ListTile(
                title: Text(budget['budgetName'] ?? '',
                  style: showDeleted
                      ? const TextStyle(decoration: TextDecoration.lineThrough, color: Colors.grey)
                      : null,
                ),
                subtitle: Text(
                  'Amount: RM ${budget['budgetAmount'] ?? '0'}\n'
                      'Start: ${formatDate(budget['startDate'])}\n'
                      'End: ${formatDate(budget['endDate'])}',
                ),
                trailing: showDeleted  ? IconButton(
                  icon: const Icon(Icons.restore, color: Colors.green),
                  onPressed: () => recoverBudget(budget['budgetID']),
                )
                    : Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    IconButton(
                      icon: const Icon(Icons.edit, color: Colors.blue),
                      onPressed: () => showEditDialog(budget),
                    ),
                    IconButton(
                      icon: const Icon(Icons.delete, color: Colors.red),
                      onPressed: () => confirmDelete(budget['budgetID']),
                    ),
                  ],
                ),
              ),
            );
          },
        ),
      ),
    );
  }
}