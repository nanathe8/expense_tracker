import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import 'config.dart'; // Make sure this contains your SERVER URL

class GroupExpenseScreen extends StatefulWidget {
  const GroupExpenseScreen({super.key});

  @override
  State<GroupExpenseScreen> createState() => _GroupExpenseScreenState();
}

class _GroupExpenseScreenState extends State<GroupExpenseScreen> {
  List expenses = [];
  double groupBudget = 0.0;
  bool isLoading = true;

  @override
  void initState() {
    super.initState();
    fetchGroupData();
  }

  Future<String?> getSessionId() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('PHPSESSID');
  }

  Future<void> fetchGroupData() async {
    setState(() {
      isLoading = true;
    });

    final phpSessionId = await getSessionId();

    // Fetch expenses with session ID in headers
    final expenseResponse = await http.get(
      Uri.parse('${CONFIG.SERVER}/getGroupExpenses.php'),
      headers: {
        'Content-Type': 'application/json',
        'Cookie': 'PHPSESSID=$phpSessionId',
      },
    );

    // Fetch budget with session ID in headers
    final budgetResponse = await http.get(
      Uri.parse('${CONFIG.SERVER}/getGroupBudget.php'),
      headers: {
        'Content-Type': 'application/json',
        'Cookie': 'PHPSESSID=$phpSessionId',
      },
    );

    if (expenseResponse.statusCode == 200 && budgetResponse.statusCode == 200) {
      try {
        final expenseData = json.decode(expenseResponse.body);
        final budgetData = json.decode(budgetResponse.body);

        if (expenseData is List) {
          setState(() {
            expenses = expenseData;
            groupBudget = double.tryParse(budgetData['budget'].toString()) ?? 0.0;
            isLoading = false;
          });
        } else {
          throw Exception("Expense data is not a list");
        }
      } catch (e) {
        setState(() {
          isLoading = false;
        });
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error parsing data: $e')),
        );
      }
    } else {
      setState(() {
        isLoading = false;
      });
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Failed to load group data')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text("Group Expense"),
        backgroundColor: const Color(0xFF1E1E2E),
      ),
      backgroundColor: const Color(0xFF2A2A3A),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              "Group Budget: RM ${groupBudget.toStringAsFixed(2)}",
              style: const TextStyle(color: Colors.white, fontSize: 20),
            ),
            const SizedBox(height: 20),
            const Text(
              "Expenses:",
              style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 10),
            Expanded(
              child: expenses.isEmpty
                  ? const Text("No expenses found", style: TextStyle(color: Colors.white))
                  : ListView.builder(
                itemCount: expenses.length,
                itemBuilder: (context, index) {
                  final expense = expenses[index];
                  return Card(
                    color: const Color(0xFF3A3A4A),
                    child: ListTile(
                      title: Text(
                        expense['description'] ?? 'No Description',
                        style: const TextStyle(color: Colors.white),
                      ),
                      subtitle: Text(
                        'RM ${expense['amount']} | Date: ${expense['date']}',
                        style: const TextStyle(color: Colors.white70),
                      ),
                    ),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}
