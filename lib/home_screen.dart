import 'package:flutter/material.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'base_screen.dart'; // Import the BaseScreen for common structure
import 'config.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  _HomeScreenState createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  bool isLoading = true;
  bool isIncomeVisible = false;
  bool isExpensesVisible = false;
  double totalIncome = 0.0;
  double totalExpenses = 0.0;
  double remainingBalance = 0.0;
  List<dynamic> transactions = [];
  String errorMessage = '';

  // Fetching data from the PHP backend
  Future<void> fetchData() async {
    if (mounted) {
      setState(() {
        isLoading = true; // Trigger loading state when necessary
      });

      final url = '${CONFIG.SERVER}/mainDashboard.php'; // Your API endpoint
      try {
        final response = await http.get(Uri.parse(url));
        if (response.statusCode == 200) {
          final data = json.decode(response.body);
          setState(() {
            totalIncome = (data['total_income'] ?? 0).toDouble();
            totalExpenses = (data['total_expenses'] ?? 0).toDouble();
            remainingBalance = totalIncome - totalExpenses;
            transactions = data['transactions'] ?? [];
            isLoading = false;
          });
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
      }
    }
  }

  @override
  void initState() {
    super.initState();
    fetchData();  // Fetch data when the HomeScreen is initialized
  }

  // Function to trigger when returning to home screen
  void _onPop() {
    fetchData();  // Refresh data when returning to home screen
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
        child: Column(
          children: <Widget>[
            const SizedBox(height: 20),
            // Display Remaining Balance at the top
            Text(
              'Remaining Balance: RM ${remainingBalance.toStringAsFixed(2)}',
              style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Colors.white),
            ),
            const SizedBox(height: 30),
            // Circular Progress Indicator for remaining balance with modern design
            Stack(
              alignment: Alignment.center,
              children: [
                CircularProgressIndicator(
                  value: totalIncome != 0 ? remainingBalance / totalIncome : 0,
                  strokeWidth: 8,
                  backgroundColor: Colors.grey.shade700,
                  valueColor: AlwaysStoppedAnimation(Colors.orange),
                ),
                Text(
                  'RM ${remainingBalance.toStringAsFixed(2)}',
                  style: const TextStyle(
                    fontSize: 36,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 30),
            // Section for Total Income and Total Expenses Cards
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
              children: <Widget>[
                InfoCard(
                  title: 'Total Income',
                  value: isIncomeVisible ? 'RM ${totalIncome.toStringAsFixed(2)}' : '****',
                  color: Colors.green,
                  onToggleVisibility: toggleIncomeVisibility,
                ),
                InfoCard(
                  title: 'Total Expenses',
                  value: isExpensesVisible ? 'RM ${totalExpenses.toStringAsFixed(2)}' : '****',
                  color: Colors.red,
                  onToggleVisibility: toggleExpensesVisibility,
                ),
              ],
            ),
            const SizedBox(height: 30),
            // Show summary of latest transactions (limit to a few transactions)
            Text(
              'Recent Transactions:',
              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.white),
            ),
            const SizedBox(height: 10),
            // Show the last 3 transactions (or fewer if not available)
            isLoading
                ? const Center(child: CircularProgressIndicator())
                : errorMessage.isNotEmpty
                ? Center(child: Text(errorMessage, style: const TextStyle(color: Colors.red, fontSize: 16)))
                : Expanded(
              child: ListView.builder(
                shrinkWrap: true, // Prevents expanding beyond available space
                itemCount: transactions.length > 3 ? 3 : transactions.length,
                itemBuilder: (context, index) {
                  final transaction = transactions[index];
                  return TransactionItem(
                    date: transaction['date'],
                    description: transaction['description'],
                    amount: transaction['amount'].toDouble(),
                    type: transaction['type'],
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
                  color: Colors.white,
                ),
              ),
              const SizedBox(height: 10),
              Text(
                value,
                style: const TextStyle(
                  fontSize: 22,
                  fontWeight: FontWeight.bold,
                  color: Colors.white,
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
  final String date;
  final String description;
  final double amount;
  final String type;

  const TransactionItem({
    super.key,
    required this.date,
    required this.description,
    required this.amount,
    required this.type,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      color: Colors.black87,
      margin: const EdgeInsets.symmetric(vertical: 8),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
      child: ListTile(
        contentPadding: const EdgeInsets.all(12),
        title: Row(
          children: [
            Icon(
              type == 'Income' ? Icons.arrow_upward : Icons.arrow_downward,
              color: type == 'Income' ? Colors.green : Colors.red,
            ),
            const SizedBox(width: 10),
            Text(
              '$description ($type)',
              style: const TextStyle(color: Colors.white),
            ),
          ],
        ),
        subtitle: Text(
          'Date: $date',
          style: const TextStyle(color: Colors.white70),
        ),
        trailing: Text(
          '${type == 'Income' ? '+' : '-'} RM ${amount.toStringAsFixed(2)}',
          style: TextStyle(
            color: type == 'Income' ? Colors.green : Colors.red,
            fontWeight: FontWeight.bold,
          ),
        ),
      ),
    );
  }
}
