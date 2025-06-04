import 'package:flutter/material.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'config.dart';

class GroupDashboardScreen extends StatefulWidget {
  final String groupID;

  const GroupDashboardScreen({super.key, required this.groupID});

  @override
  State<GroupDashboardScreen> createState() => _GroupDashboardScreenState();
}

class _GroupDashboardScreenState extends State<GroupDashboardScreen> {
  bool _isLoading = true;
  String _errorMessage = '';

  // Group info
  String _groupName = '';
  String _groupDescription = '';
  List<dynamic> _members = [];

  // Expenses
  List<dynamic> _expenses = [];

  // Budget
  double? _budgetAmount;
  double? _spentAmount;
  double? _balance;

  @override
  void initState() {
    super.initState();
    fetchAllGroupData();
  }

  Future<String?> getSessionId() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('PHPSESSID');
  }

  Future<void> fetchAllGroupData() async {
    setState(() {
      _isLoading = true;
      _errorMessage = '';
    });

    try {
      await Future.wait([
        fetchGroupDetails(),
        fetchGroupExpenses(),
        fetchGroupBudget(),
      ]);

      setState(() {
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _errorMessage = 'Error loading data: $e';
        _isLoading = false;
      });
    }
  }

  Future<void> fetchGroupDetails() async {
    final phpSessionId = await getSessionId();

    final url = Uri.parse('${CONFIG.SERVER}/getGroupDetails.php');
    final response = await http.post(
      url,
      headers: {
        'Content-Type': 'application/json',
        'Cookie': 'PHPSESSID=$phpSessionId',
      },
      body: jsonEncode({'groupID': widget.groupID}),
    );

    final data = jsonDecode(response.body);
    if (data['status'] == 'success') {
      setState(() {
        _groupName = data['group']['groupName'] ?? '';
        _groupDescription = data['group']['groupDescription'] ?? '';
        _members = data['members'] ?? [];
      });
    } else {
      throw Exception(data['message'] ?? 'Failed to load group details');
    }
  }

  Future<void> fetchGroupExpenses() async {
    final url = Uri.parse('${CONFIG.SERVER}/getGroupExpenses.php');
    final response = await http.post(
      url,
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'groupID': widget.groupID}),
    );

    final data = jsonDecode(response.body);
    if (data['status'] == 'success') {
      setState(() {
        _expenses = data['expenses'] ?? [];
      });
    } else {
      throw Exception(data['message'] ?? 'Failed to load expenses');
    }
  }

  Future<void> fetchGroupBudget() async {
    final url = Uri.parse('${CONFIG.SERVER}/getGroupBudget.php');
    final response = await http.post(
      url,
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'groupID': widget.groupID}),
    );

    final data = jsonDecode(response.body);
    if (data['status'] == 'success') {
      setState(() {
        _budgetAmount = double.tryParse(data['budget']['budgetAmount'].toString()) ?? 0;
        _spentAmount = double.tryParse(data['budget']['totalExpenses'].toString()) ?? 0;
        _balance = double.tryParse(data['budget']['balance'].toString()) ?? 0;
      });
    } else {
      throw Exception(data['message'] ?? 'Failed to load budget');
    }
  }

  Widget buildMemberList() {
    if (_members.isEmpty) return const Text('No members found');

    return ListView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      itemCount: _members.length,
      itemBuilder: (context, index) {
        final member = _members[index];
        return ListTile(
          leading: CircleAvatar(
            child: Text(member['name'][0].toUpperCase()),
          ),
          title: Text(member['name']),
          subtitle: Text('Role: ${member['role']}'),
        );
      },
    );
  }

  Widget buildExpenseList() {
    if (_expenses.isEmpty) return const Text('No expenses recorded');

    return ListView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      itemCount: _expenses.length,
      itemBuilder: (context, index) {
        final exp = _expenses[index];
        return Card(
          margin: const EdgeInsets.symmetric(vertical: 4),
          child: ListTile(
            title: Text('${exp['categoryName'] ?? 'Unknown'}: \$${exp['amount']}'),
            subtitle: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Date: ${exp['date']}'),
                if ((exp['description'] ?? '').isNotEmpty)
                  Text('Desc: ${exp['description']}'),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget buildBudgetSummary() {
    if (_budgetAmount == null) return const SizedBox();

    return Card(
      color: Colors.blue.shade50,
      child: Padding(
        padding: const EdgeInsets.all(12.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Budget Summary', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            const SizedBox(height: 8),
            Text('Budget Amount: \$${_budgetAmount!.toStringAsFixed(2)}'),
            Text('Spent: \$${_spentAmount!.toStringAsFixed(2)}'),
            Text('Balance: \$${_balance!.toStringAsFixed(2)}'),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Group Dashboard'),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _errorMessage.isNotEmpty
          ? Center(child: Text(_errorMessage, style: const TextStyle(color: Colors.red)))
          : SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(_groupName, style: const TextStyle(fontSize: 26, fontWeight: FontWeight.bold)),
            const SizedBox(height: 8),
            Text(_groupDescription),
            const SizedBox(height: 20),
            buildBudgetSummary(),
            const SizedBox(height: 20),
            const Text('Members', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
            buildMemberList(),
            const SizedBox(height: 20),
            const Text('Expenses', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
            buildExpenseList(),
          ],
        ),
      ),
    );
  }
}
