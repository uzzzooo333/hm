import React, { useState, useRef, useEffect } from 'react';
import { useSearchParams } from 'react-router-dom';
import Webcam from 'react-webcam';
import * as blazeface from '@tensorflow-models/blazeface';
import * as tf from '@tensorflow/tfjs';
import axios from 'axios';
import { 
    Camera, CameraOff, UserCheck, LogIn, LogOut, 
    CheckCircle, XCircle, Loader, Clock 
} from 'lucide-react';

const FaceAttendance = () => {
    const [searchParams] = useSearchParams();
    const userId = searchParams.get('user_id') || localStorage.getItem('user_id') || '1';
    const userName = searchParams.get('name') || localStorage.getItem('user_name') || 'User';
    
    const webcamRef = useRef(null);
    const [model, setModel] = useState(null);
    const [loading, setLoading] = useState(true);
    const [cameraActive, setCameraActive] = useState(false);
    const [faceDetected, setFaceDetected] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [message, setMessage] = useState('');
    const [messageType, setMessageType] = useState(''); // 'success', 'error', 'info'
    const [todayAttendance, setTodayAttendance] = useState(null);
    const [currentTime, setCurrentTime] = useState(new Date());

    // Load face detection model
    useEffect(() => {
        loadModel();
        fetchTodayAttendance();
        
        const timer = setInterval(() => {
            setCurrentTime(new Date());
        }, 1000);

        return () => clearInterval(timer);
    }, []);

    // Detect faces continuously
    useEffect(() => {
        let interval;
        if (cameraActive && model) {
            interval = setInterval(() => {
                detectFace();
            }, 500);
        }
        return () => clearInterval(interval);
    }, [cameraActive, model]);

    const loadModel = async () => {
        try {
            await tf.ready();
            const loadedModel = await blazeface.load();
            setModel(loadedModel);
            setLoading(false);
            showMessage('Face detection ready!', 'success');
        } catch (error) {
            console.error('Error loading model:', error);
            showMessage('Failed to load face detection', 'error');
            setLoading(false);
        }
    };

    const fetchTodayAttendance = async () => {
        try {
            const today = new Date().toISOString().split('T')[0];
            const res = await axios.get(`http://localhost/mediconnect360/api/payroll/get_attendance.php?user_id=${userId}&month=${new Date().getMonth() + 1}&year=${new Date().getFullYear()}`);
            
            if (res.data.success && res.data.data) {
                const todayRecord = res.data.data.find(a => a.date === today);
                setTodayAttendance(todayRecord || null);
            }
        } catch (error) {
            console.error('Error fetching attendance:', error);
        }
    };

    const detectFace = async () => {
        if (webcamRef.current && model && !processing) {
            const video = webcamRef.current.video;
            if (video && video.readyState === 4) {
                const predictions = await model.estimateFaces(video, false);
                setFaceDetected(predictions.length > 0);
            }
        }
    };

    const captureAndMarkAttendance = async (action) => {
        setProcessing(true);
        showMessage(`Processing ${action}...`, 'info');

        try {
            // Capture image
            const imageSrc = webcamRef.current.getScreenshot();
            
            // Detect face
            if (!faceDetected) {
                showMessage('Please position your face in the camera', 'error');
                setProcessing(false);
                return;
            }

            // Mark attendance
            const res = await axios.post('http://localhost/mediconnect360/api/payroll/mark_attendance.php', {
                user_id: parseInt(userId),
                action: action,
                face_image: imageSrc
            });

            if (res.data.success) {
                showMessage(res.data.message, 'success');
                fetchTodayAttendance();
                setTimeout(() => setCameraActive(false), 2000);
            } else {
                showMessage(res.data.message || 'Failed to mark attendance', 'error');
            }
        } catch (error) {
            console.error('Error marking attendance:', error);
            showMessage('Network error. Please try again.', 'error');
        }

        setProcessing(false);
    };

    const showMessage = (msg, type) => {
        setMessage(msg);
        setMessageType(type);
        setTimeout(() => setMessage(''), 5000);
    };

    const toggleCamera = () => {
        setCameraActive(!cameraActive);
        if (cameraActive) {
            setFaceDetected(false);
        }
    };

    const canCheckIn = !todayAttendance;
    const canCheckOut = todayAttendance && todayAttendance.check_in && !todayAttendance.check_out;

    if (loading) {
        return (
            <div style={styles.loading}>
                <Loader size={48} color="#667eea" />
                <p style={styles.loadingText}>Initializing face detection...</p>
            </div>
        );
    }

    return (
        <div style={styles.container}>
            {/* Header */}
            <div style={styles.header}>
                <div>
                    <h1 style={styles.title}>Face Recognition Attendance</h1>
                    <p style={styles.subtitle}>Hello, {userName}</p>
                </div>
                <div style={styles.clockCard}>
                    <Clock size={20} color="#667eea" />
                    <div style={styles.timeDisplay}>
                        <div style={styles.time}>{currentTime.toLocaleTimeString()}</div>
                        <div style={styles.date}>{currentTime.toLocaleDateString('en-GB', { 
                            day: 'numeric', 
                            month: 'short', 
                            year: 'numeric' 
                        })}</div>
                    </div>
                </div>
            </div>

            {/* Status Message */}
            {message && (
                <div style={{
                    ...styles.messageBox,
                    background: messageType === 'success' ? '#d1fae5' : 
                               messageType === 'error' ? '#fee2e2' : '#dbeafe',
                    color: messageType === 'success' ? '#065f46' : 
                           messageType === 'error' ? '#991b1b' : '#1e40af',
                }}>
                    {messageType === 'success' && <CheckCircle size={20} />}
                    {messageType === 'error' && <XCircle size={20} />}
                    <span>{message}</span>
                </div>
            )}

            <div style={styles.content}>
                {/* Camera Section */}
                <div style={styles.cameraSection}>
                    <div style={styles.cameraCard}>
                        <div style={styles.cameraContainer}>
                            {cameraActive ? (
                                <>
                                    <Webcam
                                        ref={webcamRef}
                                        audio={false}
                                        screenshotFormat="image/jpeg"
                                        videoConstraints={{
                                            width: 640,
                                            height: 480,
                                            facingMode: 'user'
                                        }}
                                        style={styles.webcam}
                                    />
                                    
                                    {/* Face Detection Indicator */}
                                    <div style={{
                                        ...styles.faceIndicator,
                                        borderColor: faceDetected ? '#10b981' : '#ef4444',
                                    }}>
                                        <div style={{
                                            ...styles.faceStatus,
                                            background: faceDetected ? '#10b981' : '#ef4444',
                                        }}>
                                            {faceDetected ? '✓ Face Detected' : '✗ No Face Detected'}
                                        </div>
                                    </div>
                                </>
                            ) : (
                                <div style={styles.cameraOff}>
                                    <CameraOff size={64} color="#cbd5e0" />
                                    <p style={styles.cameraOffText}>Camera is off</p>
                                    <p style={styles.cameraOffSubtext}>
                                        Click the button below to activate camera for attendance
                                    </p>
                                </div>
                            )}
                        </div>

                        {/* Camera Controls */}
                        <div style={styles.cameraControls}>
                            <button 
                                onClick={toggleCamera}
                                style={{
                                    ...styles.btnSecondary,
                                    background: cameraActive ? '#ef4444' : '#667eea',
                                }}
                            >
                                {cameraActive ? <CameraOff size={20} /> : <Camera size={20} />}
                                <span>{cameraActive ? 'Turn Off Camera' : 'Turn On Camera'}</span>
                            </button>
                        </div>
                    </div>

                    {/* Action Buttons */}
                    {cameraActive && (
                        <div style={styles.actionButtons}>
                            <button 
                                onClick={() => captureAndMarkAttendance('check_in')}
                                disabled={!canCheckIn || !faceDetected || processing}
                                style={{
                                    ...styles.btnAction,
                                    ...styles.btnCheckIn,
                                    opacity: canCheckIn && faceDetected && !processing ? 1 : 0.5,
                                    cursor: canCheckIn && faceDetected && !processing ? 'pointer' : 'not-allowed',
                                }}
                            >
                                {processing ? <Loader size={20} /> : <LogIn size={20} />}
                                <span>Check In</span>
                            </button>

                            <button 
                                onClick={() => captureAndMarkAttendance('check_out')}
                                disabled={!canCheckOut || !faceDetected || processing}
                                style={{
                                    ...styles.btnAction,
                                    ...styles.btnCheckOut,
                                    opacity: canCheckOut && faceDetected && !processing ? 1 : 0.5,
                                    cursor: canCheckOut && faceDetected && !processing ? 'pointer' : 'not-allowed',
                                }}
                            >
                                {processing ? <Loader size={20} /> : <LogOut size={20} />}
                                <span>Check Out</span>
                            </button>
                        </div>
                    )}
                </div>

                {/* Today's Status */}
                <div style={styles.statusSection}>
                    <div style={styles.statusCard}>
                        <h2 style={styles.statusTitle}>
                            <UserCheck size={24} color="#667eea" />
                            <span>Today's Status</span>
                        </h2>

                        {todayAttendance ? (
                            <div style={styles.statusGrid}>
                                <div style={styles.statusItem}>
                                    <div style={styles.statusLabel}>Check In</div>
                                    <div style={{...styles.statusValue, color: '#10b981'}}>
                                        {todayAttendance.check_in || '-'}
                                    </div>
                                </div>
                                <div style={styles.statusItem}>
                                    <div style={styles.statusLabel}>Check Out</div>
                                    <div style={{...styles.statusValue, color: '#ef4444'}}>
                                        {todayAttendance.check_out || 'Pending'}
                                    </div>
                                </div>
                                <div style={styles.statusItem}>
                                    <div style={styles.statusLabel}>Work Hours</div>
                                    <div style={styles.statusValue}>
                                        {todayAttendance.work_hours ? `${todayAttendance.work_hours}h` : '-'}
                                    </div>
                                </div>
                                <div style={styles.statusItem}>
                                    <div style={styles.statusLabel}>Status</div>
                                    <div>
                                        <span style={{
                                            ...styles.statusBadge,
                                            background: todayAttendance.status === 'present' ? '#d1fae5' : '#fee2e2',
                                            color: todayAttendance.status === 'present' ? '#065f46' : '#991b1b',
                                        }}>
                                            {todayAttendance.status.charAt(0).toUpperCase() + todayAttendance.status.slice(1)}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        ) : (
                            <div style={styles.noStatus}>
                                <XCircle size={48} color="#cbd5e0" />
                                <p style={styles.noStatusText}>No attendance marked today</p>
                                <p style={styles.noStatusSubtext}>Use camera to check in</p>
                            </div>
                        )}
                    </div>

                    {/* Instructions */}
                    <div style={styles.instructionsCard}>
                        <h3 style={styles.instructionsTitle}>Instructions</h3>
                        <ul style={styles.instructionsList}>
                            <li>✓ Turn on the camera to start</li>
                            <li>✓ Position your face in the frame</li>
                            <li>✓ Wait for face detection confirmation</li>
                            <li>✓ Click Check In at the start of your shift</li>
                            <li>✓ Click Check Out at the end of your shift</li>
                            <li>✓ Ensure good lighting for best results</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    );
};

const styles = {
    container: {
        minHeight: '100vh',
        background: 'linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%)',
        padding: '24px',
    },
    loading: {
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        justifyContent: 'center',
        height: '100vh',
        background: '#f8fafc',
    },
    loadingText: {
        marginTop: '16px',
        color: '#64748b',
        fontSize: '16px',
    },
    header: {
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: '24px',
        flexWrap: 'wrap',
        gap: '16px',
    },
    title: {
        fontSize: '28px',
        fontWeight: '700',
        color: '#1e293b',
        margin: 0,
    },
    subtitle: {
        color: '#64748b',
        fontSize: '15px',
        marginTop: '4px',
    },
    clockCard: {
        background: '#fff',
        padding: '16px 24px',
        borderRadius: '12px',
        display: 'flex',
        alignItems: 'center',
        gap: '12px',
        boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
    },
    timeDisplay: {
        display: 'flex',
        flexDirection: 'column',
    },
    time: {
        fontSize: '20px',
        fontWeight: '700',
        color: '#1e293b',
        lineHeight: 1,
    },
    date: {
        fontSize: '12px',
        color: '#64748b',
        marginTop: '4px',
    },
    messageBox: {
        display: 'flex',
        alignItems: 'center',
        gap: '12px',
        padding: '16px 20px',
        borderRadius: '12px',
        marginBottom: '24px',
        fontWeight: '600',
        fontSize: '14px',
        animation: 'slideDown 0.3s ease',
    },
    content: {
        display: 'grid',
        gridTemplateColumns: '1fr 400px',
        gap: '24px',
    },
    cameraSection: {
        display: 'flex',
        flexDirection: 'column',
        gap: '16px',
    },
    cameraCard: {
        background: '#fff',
        borderRadius: '16px',
        overflow: 'hidden',
        boxShadow: '0 4px 12px rgba(0,0,0,0.1)',
    },
    cameraContainer: {
        position: 'relative',
        aspectRatio: '4/3',
        background: '#000',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
    },
    webcam: {
        width: '100%',
        height: '100%',
        objectFit: 'cover',
    },
    faceIndicator: {
        position: 'absolute',
        top: '20px',
        left: '20px',
        right: '20px',
        bottom: '20px',
        border: '3px solid',
        borderRadius: '12px',
        pointerEvents: 'none',
        transition: 'border-color 0.3s',
    },
    faceStatus: {
        position: 'absolute',
        top: '16px',
        left: '50%',
        transform: 'translateX(-50%)',
        padding: '8px 16px',
        borderRadius: '20px',
        color: '#fff',
        fontSize: '14px',
        fontWeight: '600',
    },
    cameraOff: {
        textAlign: 'center',
        padding: '40px',
        color: '#94a3b8',
    },
    cameraOffText: {
        fontSize: '18px',
        fontWeight: '600',
        marginTop: '16px',
        color: '#64748b',
    },
    cameraOffSubtext: {
        fontSize: '14px',
        marginTop: '8px',
        color: '#94a3b8',
    },
    cameraControls: {
        padding: '20px',
        background: '#f8fafc',
        borderTop: '1px solid #e2e8f0',
    },
    btnSecondary: {
        width: '100%',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        gap: '10px',
        padding: '14px',
        border: 'none',
        borderRadius: '10px',
        color: '#fff',
        fontSize: '15px',
        fontWeight: '600',
        cursor: 'pointer',
        transition: 'all 0.2s',
    },
    actionButtons: {
        display: 'grid',
        gridTemplateColumns: '1fr 1fr',
        gap: '16px',
    },
    btnAction: {
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        gap: '10px',
        padding: '16px',
        border: 'none',
        borderRadius: '12px',
        fontSize: '16px',
        fontWeight: '700',
        color: '#fff',
        transition: 'all 0.2s',
    },
    btnCheckIn: {
        background: 'linear-gradient(135deg, #10b981, #059669)',
    },
    btnCheckOut: {
        background: 'linear-gradient(135deg, #ef4444, #dc2626)',
    },
    statusSection: {
        display: 'flex',
        flexDirection: 'column',
        gap: '16px',
    },
    statusCard: {
        background: '#fff',
        padding: '24px',
        borderRadius: '16px',
        boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
    },
    statusTitle: {
        display: 'flex',
        alignItems: 'center',
        gap: '10px',
        fontSize: '20px',
        fontWeight: '700',
        color: '#1e293b',
        marginBottom: '20px',
    },
    statusGrid: {
        display: 'grid',
        gridTemplateColumns: '1fr 1fr',
        gap: '16px',
    },
    statusItem: {
        padding: '16px',
        background: '#f8fafc',
        borderRadius: '10px',
    },
    statusLabel: {
        fontSize: '13px',
        color: '#64748b',
        fontWeight: '500',
        marginBottom: '6px',
    },
    statusValue: {
        fontSize: '20px',
        fontWeight: '700',
        color: '#1e293b',
    },
    statusBadge: {
        padding: '6px 12px',
        borderRadius: '12px',
        fontSize: '12px',
        fontWeight: '600',
        display: 'inline-block',
    },
    noStatus: {
        textAlign: 'center',
        padding: '40px 20px',
    },
    noStatusText: {
        fontSize: '16px',
        fontWeight: '600',
        color: '#64748b',
        marginTop: '16px',
    },
    noStatusSubtext: {
        fontSize: '14px',
        color: '#94a3b8',
        marginTop: '6px',
    },
    instructionsCard: {
        background: 'linear-gradient(135deg, #667eea, #764ba2)',
        padding: '24px',
        borderRadius: '16px',
        color: '#fff',
    },
    instructionsTitle: {
        fontSize: '18px',
        fontWeight: '700',
        marginBottom: '16px',
    },
    instructionsList: {
        listStyle: 'none',
        padding: 0,
        margin: 0,
    },
};

// Add CSS animation
const styleSheet = document.styleSheets[0];
const keyframes = `
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}`;
try {
    styleSheet.insertRule(keyframes, styleSheet.cssRules.length);
} catch (e) {}

export default FaceAttendance;
