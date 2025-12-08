// frontend/src/App.js
import React, { useState } from 'react';
import './App.css';

function App() {
  const [users, setUsers] = useState([]);

  // Fetch users from backend
  const fetchUsers = async () => {
    const res = await fetch('/api/users');
    const data = await res.json();
    setUsers(data);
  };

  return (
    <div className="App">
      <h1>User List</h1>
      <button onClick={fetchUsers}>Get Users</button>
      <ul>
        {users.map(user => (
          <li key={user.id}>{user.name} - {user.email}</li>
        ))}
      </ul>
    </div>
  );
}

export default App;
