import React from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import VideoRoom from './VideoRoom';
import WaitingRoom from './components/WaitingRoom';
import PayrollDashboard from './pages/PayrollDashboard';
import FaceAttendance from './pages/FaceAttendance';

function App() {
  return (
    <BrowserRouter>
      <Routes>
        {/* Telemedicine */}
        <Route path="/meet/:meetingId" element={<VideoRoom />} />
        <Route path="/waiting/:meetingId" element={<WaitingRoom />} />
        
        {/* Payroll & Attendance */}
        <Route path="/payroll" element={<PayrollDashboard />} />
        <Route path="/attendance" element={<FaceAttendance />} />
        
        {/* Default */}
        <Route path="/" element={<Navigate to="/attendance?user_id=1&name=Demo%20User" replace />} />
      </Routes>
    </BrowserRouter>
  );
}

export default App;
