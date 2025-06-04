import 'package:flutter/material.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;

import 'config.dart';
import 'home_screen.dart';

class JoinGroupScreen extends StatefulWidget {
  @override
  _JoinGroupScreenState createState() => _JoinGroupScreenState();
}

class _JoinGroupScreenState extends State<JoinGroupScreen> {
  final _formKey = GlobalKey<FormState>();
  final _inviteTokenController = TextEditingController();

  bool _isLoading = false;
  String _message = '';

  Future<void> joinGroup() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isLoading = true;
      _message = '';
    });

    final url = Uri.parse('${CONFIG.SERVER}/joinGroup.php'); // Change this
    final body = jsonEncode({
      'inviteToken': _inviteTokenController.text.trim(),
    });

    try {
      final phpSessionId = await getSessionId();

      final response = await http.post(
        url,
        headers: {
          'Content-Type': 'application/json',
          'Cookie': 'PHPSESSID=$phpSessionId',
          // Add auth/session headers if needed
        },
        body: body,
      );

      final data = jsonDecode(response.body);

      if (data['status'] == 'success') {
        setState(() {
          _message = 'Success! You joined the group.';
        });
        // Optionally navigate to group dashboard
      } else {
        setState(() {
          _message = 'Failed: ${data['message']}';
        });
      }
    } catch (e) {
      setState(() {
        _message = 'Error: $e';
      });
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  @override
  void dispose() {
    _inviteTokenController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Join Group')),
      body: Padding(
        padding: EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            children: [
              TextFormField(
                controller: _inviteTokenController,
                decoration: InputDecoration(labelText: 'Invite Token'),
                validator: (value) =>
                value == null || value.isEmpty ? 'Please enter invite token' : null,
              ),
              SizedBox(height: 20),
              _isLoading
                  ? CircularProgressIndicator()
                  : ElevatedButton(
                onPressed: joinGroup,
                child: Text('Join Group'),
              ),
              SizedBox(height: 20),
              Text(_message, style: TextStyle(color: Colors.red)),
            ],
          ),
        ),
      ),
    );
  }
}
