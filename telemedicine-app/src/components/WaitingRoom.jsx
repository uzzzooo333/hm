import React, { useState, useEffect } from 'react';
import { useParams, useSearchParams, useNavigate } from 'react-router-dom';
import { Video, VideoOff, Mic, MicOff, Settings } from 'lucide-react';
import axios from 'axios';
import { API_BASE } from '../config';

const WaitingRoom = () => {
    const { meetingId } = useParams();
    const [searchParams] = useSearchParams();
    const navigate = useNavigate();
    
    const [userName, setUserName] = useState(searchParams.get('name') || '');
    const [doctorName, setDoctorName] = useState(searchParams.get('doctor') || '');
    const [isVideoOn, setIsVideoOn] = useState(true);
    const [isAudioOn, setIsAudioOn] = useState(true);
    const [isReady, setIsReady] = useState(false);
    const [connecting, setConnecting] = useState(false);
    const [connectError, setConnectError] = useState(null);

    useEffect(() => {
        if (userName) setIsReady(true);
    }, [userName]);

    const joinMeeting = async () => {
        setConnecting(true);
        setConnectError(null);
        const role = searchParams.get('role') || 'patient';
        const audio = isAudioOn ? '1' : '0';
        const video = isVideoOn ? '1' : '0';
        let targetMeetingId = meetingId;

        if (doctorName.trim()) {
            try {
                const res = await axios.post(`${API_BASE}/connect_doctor.php`, {
                    doctor_name: doctorName.trim(),
                    patient_name: userName || 'Guest'
                });
                if (res.data?.success) {
                    targetMeetingId = res.data.meeting_id;
                } else {
                    setConnectError(res.data?.error || 'Unable to connect to doctor');
                    setConnecting(false);
                    return;
                }
            } catch (err) {
                console.error(err);
                setConnectError('Unable to connect to doctor');
                setConnecting(false);
                return;
            }
        }

        if (!targetMeetingId) {
            setConnectError('Missing meeting ID');
            setConnecting(false);
            return;
        }

        navigate(`/meet/${targetMeetingId}?name=${encodeURIComponent(userName)}&role=${role}&audio=${audio}&video=${video}`);
    };

    return (
        <div style={styles.container}>
            <div style={styles.content}>
                {/* Logo */}
                <div style={styles.logo}>
                    <div style={styles.logoIcon}>🏥</div>
                    <h2 style={styles.logoText}>MediConnect360</h2>
                </div>

                {/* Video Preview */}
                <div style={styles.previewCard}>
                    <div style={styles.videoPreview}>
                        {isVideoOn ? (
                            <div style={styles.videoPlaceholder}>
                                <div style={styles.userAvatar}>
                                    {userName ? userName.charAt(0).toUpperCase() : '👤'}
                                </div>
                                <p style={styles.previewText}>Camera Preview</p>
                            </div>
                        ) : (
                            <div style={styles.videoOff}>
                                <VideoOff size={48} color="#fff" />
                                <p style={styles.previewText}>Camera Off</p>
                            </div>
                        )}
                    </div>

                    {/* Controls */}
                    <div style={styles.previewControls}>
                        <button 
                            style={{...styles.controlBtn, background: isAudioOn ? '#4a5568' : '#e53e3e'}}
                            onClick={() => setIsAudioOn(!isAudioOn)}
                        >
                            {isAudioOn ? <Mic size={20} /> : <MicOff size={20} />}
                        </button>
                        <button 
                            style={{...styles.controlBtn, background: isVideoOn ? '#4a5568' : '#e53e3e'}}
                            onClick={() => setIsVideoOn(!isVideoOn)}
                        >
                            {isVideoOn ? <Video size={20} /> : <VideoOff size={20} />}
                        </button>
                        <button style={styles.controlBtn}>
                            <Settings size={20} />
                        </button>
                    </div>
                </div>

                {/* Name Input */}
                <div style={styles.formSection}>
                    <label style={styles.label}>Your Name</label>
                    <input 
                        type="text"
                        value={userName}
                        onChange={(e) => setUserName(e.target.value)}
                        placeholder="Enter your full name"
                        style={styles.input}
                    />
                </div>
                <div style={styles.formSection}>
                    <label style={styles.label}>Doctor Name</label>
                    <input 
                        type="text"
                        value={doctorName}
                        onChange={(e) => setDoctorName(e.target.value)}
                        placeholder="Enter doctor's name"
                        style={styles.input}
                    />
                </div>

                {/* Join Button */}
                <button 
                    onClick={joinMeeting}
                    disabled={!isReady || connecting}
                    style={{
                        ...styles.joinBtn,
                        opacity: isReady && !connecting ? 1 : 0.5,
                        cursor: isReady && !connecting ? 'pointer' : 'not-allowed'
                    }}
                >
                    {connecting ? 'Connecting...' : 'Join Video Consultation'}
                </button>
                {connectError && (
                    <p style={styles.errorText}>{connectError}</p>
                )}

                <p style={styles.footer}>
                    By joining, you agree to our terms and privacy policy
                </p>
            </div>
        </div>
    );
};

const styles = {
    container: {
        minHeight: '100vh',
        background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        padding: '20px',
    },
    content: {
        maxWidth: '500px',
        width: '100%',
    },
    logo: {
        textAlign: 'center',
        marginBottom: '30px',
    },
    logoIcon: {
        fontSize: '3rem',
        marginBottom: '10px',
    },
    logoText: {
        color: 'white',
        fontSize: '1.8rem',
        fontWeight: 'bold',
        margin: 0,
    },
    previewCard: {
        background: 'white',
        borderRadius: '16px',
        padding: '20px',
        marginBottom: '20px',
        boxShadow: '0 10px 40px rgba(0,0,0,0.15)',
    },
    videoPreview: {
        background: '#000',
        borderRadius: '12px',
        aspectRatio: '16/9',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        marginBottom: '15px',
        overflow: 'hidden',
    },
    videoPlaceholder: {
        textAlign: 'center',
    },
    videoOff: {
        textAlign: 'center',
    },
    userAvatar: {
        width: '80px',
        height: '80px',
        borderRadius: '50%',
        background: 'linear-gradient(135deg, #667eea, #764ba2)',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        fontSize: '2rem',
        fontWeight: 'bold',
        color: 'white',
        margin: '0 auto 10px',
    },
    previewText: {
        color: '#fff',
        margin: '10px 0 0 0',
    },
    previewControls: {
        display: 'flex',
        gap: '10px',
        justifyContent: 'center',
    },
    controlBtn: {
        width: '48px',
        height: '48px',
        borderRadius: '50%',
        border: 'none',
        color: 'white',
        cursor: 'pointer',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        transition: 'all 0.2s',
    },
    formSection: {
        background: 'white',
        borderRadius: '12px',
        padding: '20px',
        marginBottom: '20px',
    },
    label: {
        display: 'block',
        marginBottom: '8px',
        fontWeight: '600',
        color: '#2d3748',
    },
    input: {
        width: '100%',
        padding: '12px',
        border: '2px solid #e2e8f0',
        borderRadius: '8px',
        fontSize: '16px',
        outline: 'none',
    },
    joinBtn: {
        width: '100%',
        padding: '16px',
        background: 'linear-gradient(135deg, #10b981, #059669)',
        color: 'white',
        border: 'none',
        borderRadius: '12px',
        fontSize: '16px',
        fontWeight: 'bold',
        transition: 'all 0.3s',
    },
    footer: {
        textAlign: 'center',
        color: 'rgba(255,255,255,0.8)',
        fontSize: '12px',
        marginTop: '15px',
    },
    errorText: {
        marginTop: '10px',
        color: '#fee2e2',
        textAlign: 'center',
        fontSize: '13px',
    },
};

export default WaitingRoom;
