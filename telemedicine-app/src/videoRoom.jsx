import React, { useState, useEffect, useRef } from 'react';
import { JitsiMeeting } from '@jitsi/react-sdk';
import { useParams, useSearchParams, useNavigate } from 'react-router-dom';
import axios from 'axios';
import { API_BASE, JITSI_DOMAIN } from './config';
import { 
    Mic, MicOff, Video, VideoOff, Monitor, PhoneOff, Settings,
    MessageSquare, Users, FileText, Save, Plus, Trash2, Upload,
    Activity, CheckCircle, Clock, AlertCircle, X, File
} from 'lucide-react';

const VideoRoom = () => {
    const { meetingId } = useParams();
    const [searchParams] = useSearchParams();
    const navigate = useNavigate();
    const apiRef = useRef(null);
    
    const userName = searchParams.get('name') || 'Guest';
    const userRole = searchParams.get('role') || 'patient';
    const startAudioMuted = searchParams.get('audio') === '0';
    const startVideoMuted = searchParams.get('video') === '0';

    // States
    const [notes, setNotes] = useState('');
    const [medicines, setMedicines] = useState([{ name: '', dosage: '', duration: '5 days' }]);
    const [activeTab, setActiveTab] = useState('chat');
    const [saving, setSaving] = useState(false);
    const [saveStatus, setSaveStatus] = useState(null);
    const [sessionTime, setSessionTime] = useState(0);
    const [chatMessages, setChatMessages] = useState([]);
    const [newMessage, setNewMessage] = useState('');
    const [showEndConfirm, setShowEndConfirm] = useState(false);
    const [connectionStatus, setConnectionStatus] = useState('connecting');
    const [files, setFiles] = useState([]);
    const [uploading, setUploading] = useState(false);
    const [fileError, setFileError] = useState(null);
    const [isApiReady, setIsApiReady] = useState(false);
    
    // Controls
    const [isMuted, setIsMuted] = useState(startAudioMuted);
    const [isVideoOff, setIsVideoOff] = useState(startVideoMuted);
    const [isScreenSharing, setIsScreenSharing] = useState(false);
    const [jwtToken, setJwtToken] = useState(null);
    const [jitsiDomain, setJitsiDomain] = useState(JITSI_DOMAIN);
    const [jitsiError, setJitsiError] = useState(null);

    // Timer starts only after meeting is connected
    useEffect(() => {
        if (connectionStatus !== 'connected') return;
        const timer = setInterval(() => setSessionTime(prev => prev + 1), 1000);
        return () => clearInterval(timer);
    }, [connectionStatus]);

    // Fetch JWT for self-hosted Jitsi
    useEffect(() => {
        let mounted = true;
        const fetchToken = async () => {
            try {
                const res = await axios.post(`${API_BASE}/jitsi_token.php`, {
                    meeting_id: meetingId,
                    name: userName,
                    role: userRole
                });
                if (!mounted) return;
                if (res.data?.success) {
                    setJwtToken(res.data.token);
                    if (res.data.domain) setJitsiDomain(res.data.domain);
                } else {
                    setJitsiError(res.data?.error || 'Failed to fetch token');
                }
            } catch (err) {
                console.error(err);
                if (mounted) setJitsiError('Failed to fetch token');
            }
        };
        fetchToken();
        return () => { mounted = false; };
    }, [meetingId, userName, userRole]);

    const formatTime = (seconds) => {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    };

    const logEvent = async (action, details = {}) => {
        try {
            await axios.post(`${API_BASE}/telemedicine_log.php`, {
                meeting_id: meetingId,
                actor: userName,
                role: userRole,
                action,
                details
            });
        } catch (err) {
            console.error(err);
        }
    };

    const handleSave = async () => {
        setSaving(true);
        try {
            await axios.post(`${API_BASE}/save_consultation.php`, {
                meeting_id: meetingId,
                doctor_name: userName,
                notes: notes,
                prescription: medicines
            });
            setSaveStatus('success');
            setTimeout(() => setSaveStatus(null), 3000);
            logEvent('consultation_saved', { notes_length: notes?.length || 0 });
        } catch (error) {
            console.error(error);
            setSaveStatus('error');
        }
        setSaving(false);
    };

    const addMedicine = () => {
        setMedicines([...medicines, { name: '', dosage: '', duration: '5 days' }]);
    };

    const removeMedicine = (index) => {
        setMedicines(medicines.filter((_, i) => i !== index));
    };

    const updateMedicine = (index, field, value) => {
        const updated = [...medicines];
        updated[index][field] = value;
        setMedicines(updated);
    };

    const sendMessage = () => {
        if (!newMessage.trim()) return;
        setChatMessages([...chatMessages, { 
            sender: userName, 
            text: newMessage, 
            time: new Date().toLocaleTimeString() 
        }]);
        setNewMessage('');
    };

    const handleApiReady = (api) => {
        apiRef.current = api;
        setIsApiReady(true);
        api.addListener('audioMuteStatusChanged', (evt) => setIsMuted(Boolean(evt.muted)));
        api.addListener('videoMuteStatusChanged', (evt) => setIsVideoOff(Boolean(evt.muted)));
        api.addListener('screenSharingStatusChanged', (evt) => setIsScreenSharing(Boolean(evt.on)));
        api.addListener('videoConferenceJoined', () => {
            setConnectionStatus('connected');
            logEvent('joined');
        });
        api.addListener('videoConferenceLeft', () => {
            setConnectionStatus('left');
            logEvent('left');
        });
        api.addListener('conferenceFailed', (evt) => {
            setConnectionStatus('failed');
            logEvent('conference_failed', evt || {});
        });
        api.addListener('connectionFailed', () => setConnectionStatus('failed'));
        api.addListener('connectionInterrupted', () => setConnectionStatus('interrupted'));
        api.addListener('connectionRestored', () => setConnectionStatus('connected'));
    };

    const toggleMute = async () => {
        const api = apiRef.current;
        if (!api) return;
        const muted = await api.isAudioMuted();
        api.executeCommand(muted ? 'unmute' : 'mute');
    };

    const toggleVideo = async () => {
        const api = apiRef.current;
        if (!api) return;
        api.executeCommand('toggleVideo');
    };

    const toggleScreenShare = () => {
        const api = apiRef.current;
        if (!api) return;
        api.executeCommand('toggleShareScreen');
    };

    const openDeviceSettings = () => {
        const api = apiRef.current;
        if (!api) return;
        api.executeCommand('toggleSettings');
    };

    const fetchFiles = async () => {
        try {
            const res = await axios.get(`${API_BASE}/list_consultation_files.php`, {
                params: { meeting_id: meetingId }
            });
            if (res.data?.success) setFiles(res.data.files || []);
        } catch (err) {
            console.error(err);
        }
    };

    useEffect(() => {
        fetchFiles();
    }, [meetingId]);

    const handleFileUpload = async (e) => {
        const file = e.target.files?.[0];
        if (!file) return;
        setUploading(true);
        setFileError(null);
        try {
            const form = new FormData();
            form.append('file', file);
            form.append('meeting_id', meetingId);
            form.append('actor', userName);
            const res = await axios.post(`${API_BASE}/upload_consultation_file.php`, form, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            if (res.data?.success) {
                await fetchFiles();
                logEvent('file_uploaded', { name: file.name });
            } else {
                setFileError(res.data?.error || 'Upload failed');
            }
        } catch (err) {
            console.error(err);
            setFileError('Upload failed');
        }
        setUploading(false);
        e.target.value = '';
    };

    const endCall = () => {
        const api = apiRef.current;
        if (api) {
            api.executeCommand('hangup');
            api.dispose();
        }
        navigate(`/waiting/${meetingId}?name=${encodeURIComponent(userName)}&role=${userRole}`);
    };

    return (
        <div style={styles.container}>
            {/* Main Video Area */}
            <div style={styles.videoSection}>
                {/* Header */}
                <div style={styles.header}>
                    <div style={styles.headerLeft}>
                        <div style={styles.logoSmall}>🏥</div>
                        <div>
                            <h3 style={styles.headerTitle}>Video Consultation</h3>
                            <p style={styles.headerSubtitle}>{userName}</p>
                        </div>
                    </div>
                    <div style={styles.headerRight}>
                        <div style={styles.timer}>
                            <Clock size={16} />
                            <span>{formatTime(sessionTime)}</span>
                        </div>
                        <div style={styles.statusBadge}>
                            <div style={styles.statusDot}></div>
                            {connectionStatus === 'connected' ? 'Live' : connectionStatus}
                        </div>
                    </div>
                </div>

                {/* Jitsi Video */}
                <div style={styles.videoContainer}>
                    {!jitsiDomain && (
                        <div style={styles.videoLoading}>Jitsi domain not configured.</div>
                    )}
                    {jitsiError && (
                        <div style={styles.videoLoading}>{jitsiError}</div>
                    )}
                    {jitsiDomain && !jitsiError && (
                        <JitsiMeeting
                            domain={jitsiDomain}
                            roomName={`MediConnect360-${meetingId}`}
                            jwt={jwtToken || undefined}
                        configOverwrite={{
                            startWithAudioMuted: startAudioMuted,
                            startWithVideoMuted: startVideoMuted,
                            disableDeepLinking: true,
                            prejoinPageEnabled: false,
                            hideConferenceSubject: true,
                        }}
                        interfaceConfigOverwrite={{
                            TOOLBAR_BUTTONS: [],
                            SHOW_JITSI_WATERMARK: false,
                        }}
                        userInfo={{ displayName: userName }}
                        onApiReady={handleApiReady}
                        getIFrameRef={(iframeRef) => { 
                            if (iframeRef) iframeRef.style.height = '100%'; 
                        }}
                        />
                    )}
                </div>

                {/* Custom Controls Bar */}
                <div style={styles.controlsBar}>
                    <div style={styles.controlsGroup}>
                        <button 
                            style={{
                                ...styles.controlButton,
                                ...( !isApiReady ? styles.controlDisabled : null),
                                background: isMuted ? '#ef4444' : '#4a5568'
                            }}
                            onClick={toggleMute}
                            title={isMuted ? 'Unmute' : 'Mute'}
                            disabled={!isApiReady}
                        >
                            {isMuted ? <MicOff size={20} /> : <Mic size={20} />}
                        </button>

                        <button 
                            style={{
                                ...styles.controlButton,
                                ...( !isApiReady ? styles.controlDisabled : null),
                                background: isVideoOff ? '#ef4444' : '#4a5568'
                            }}
                            onClick={toggleVideo}
                            title={isVideoOff ? 'Start Video' : 'Stop Video'}
                            disabled={!isApiReady}
                        >
                            {isVideoOff ? <VideoOff size={20} /> : <Video size={20} />}
                        </button>

                        <button 
                            style={{
                                ...styles.controlButton,
                                ...( !isApiReady ? styles.controlDisabled : null),
                                background: isScreenSharing ? '#3b82f6' : '#4a5568'
                            }}
                            onClick={toggleScreenShare}
                            title="Share Screen"
                            disabled={!isApiReady}
                        >
                            <Monitor size={20} />
                        </button>
                        <button 
                            style={{
                                ...styles.controlButton,
                                ...( !isApiReady ? styles.controlDisabled : null),
                                background: '#4a5568'
                            }}
                            onClick={openDeviceSettings}
                            title="Device Settings"
                            disabled={!isApiReady}
                        >
                            <Settings size={20} />
                        </button>

                        <button 
                            style={{...styles.controlButton, background: '#ef4444', minWidth: '120px'}}
                            onClick={() => setShowEndConfirm(true)}
                            title="End Call"
                        >
                            <PhoneOff size={20} />
                            <span style={{marginLeft: '8px'}}>End Call</span>
                        </button>
                    </div>
                </div>
            </div>

            {/* Sidebar (Doctor Console or Chat) */}
            <div style={styles.sidebar}>
                {/* Tabs */}
                <div style={styles.tabs}>
                    <button 
                        style={{...styles.tab, ...(activeTab === 'chat' && styles.tabActive)}}
                        onClick={() => setActiveTab('chat')}
                    >
                        <MessageSquare size={18} />
                        <span>Chat</span>
                    </button>
                    <button 
                        style={{...styles.tab, ...(activeTab === 'participants' && styles.tabActive)}}
                        onClick={() => setActiveTab('participants')}
                    >
                        <Users size={18} />
                        <span>People</span>
                    </button>
                    <button 
                        style={{...styles.tab, ...(activeTab === 'files' && styles.tabActive)}}
                        onClick={() => setActiveTab('files')}
                    >
                        <File size={18} />
                        <span>Files</span>
                    </button>
                    {userRole === 'doctor' && (
                        <button 
                            style={{...styles.tab, ...(activeTab === 'console' && styles.tabActive)}}
                            onClick={() => setActiveTab('console')}
                        >
                            <FileText size={18} />
                            <span>Console</span>
                        </button>
                    )}
                </div>

                {/* Tab Content */}
                <div style={styles.tabContent}>
                    {/* Chat Tab */}
                    {activeTab === 'chat' && (
                        <div style={styles.chatContainer}>
                            <div style={styles.chatMessages}>
                                {chatMessages.length === 0 ? (
                                    <div style={styles.emptyState}>
                                        <MessageSquare size={48} color="#cbd5e0" />
                                        <p style={styles.emptyText}>No messages yet</p>
                                    </div>
                                ) : (
                                    chatMessages.map((msg, idx) => (
                                        <div key={idx} style={styles.chatMessage}>
                                            <div style={styles.chatHeader}>
                                                <strong style={styles.chatSender}>{msg.sender}</strong>
                                                <span style={styles.chatTime}>{msg.time}</span>
                                            </div>
                                            <p style={styles.chatText}>{msg.text}</p>
                                        </div>
                                    ))
                                )}
                            </div>
                            <div style={styles.chatInput}>
                                <input 
                                    type="text"
                                    value={newMessage}
                                    onChange={(e) => setNewMessage(e.target.value)}
                                    onKeyPress={(e) => e.key === 'Enter' && sendMessage()}
                                    placeholder="Type a message..."
                                    style={styles.input}
                                />
                                <button onClick={sendMessage} style={styles.sendButton}>
                                    Send
                                </button>
                            </div>
                        </div>
                    )}

                    {/* Participants Tab */}
                    {activeTab === 'participants' && (
                        <div style={styles.participantsContainer}>
                            <div style={styles.participant}>
                                <div style={styles.participantAvatar}>👨‍⚕️</div>
                                <div style={styles.participantInfo}>
                                    <div style={styles.participantName}>Dr. {userName}</div>
                                    <div style={styles.participantStatus}>Host • Joined</div>
                                </div>
                            </div>
                            <div style={styles.participant}>
                                <div style={styles.participantAvatar}>👤</div>
                                <div style={styles.participantInfo}>
                                    <div style={styles.participantName}>Patient</div>
                                    <div style={styles.participantStatus}>Guest • Joined</div>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Files Tab */}
                    {activeTab === 'files' && (
                        <div style={styles.filesContainer}>
                            <div style={styles.filesHeader}>
                                <label style={styles.uploadButton}>
                                    <Upload size={16} />
                                    <span>{uploading ? 'Uploading...' : 'Upload File'}</span>
                                    <input
                                        type="file"
                                        onChange={handleFileUpload}
                                        disabled={uploading}
                                        style={styles.fileInput}
                                    />
                                </label>
                                <button onClick={fetchFiles} style={styles.refreshButton}>
                                    Refresh
                                </button>
                            </div>
                            {fileError && (
                                <div style={styles.errorAlert}>
                                    <AlertCircle size={16} />
                                    <span>{fileError}</span>
                                </div>
                            )}
                            <div style={styles.fileList}>
                                {files.length === 0 ? (
                                    <div style={styles.emptyState}>
                                        <File size={48} color="#cbd5e0" />
                                        <p style={styles.emptyText}>No files shared</p>
                                    </div>
                                ) : (
                                    files.map((f, idx) => (
                                        <div key={idx} style={styles.fileRow}>
                                            <div>
                                                <div style={styles.fileName}>{f.name}</div>
                                                <div style={styles.fileMeta}>{f.size_kb} KB â€¢ {f.mtime}</div>
                                            </div>
                                            <a href={f.url} target="_blank" rel="noreferrer" style={styles.fileLink}>
                                                Open
                                            </a>
                                        </div>
                                    ))
                                )}
                            </div>
                        </div>
                    )}

                    {/* Doctor Console Tab */}
                    {activeTab === 'console' && userRole === 'doctor' && (
                        <div style={styles.consoleContainer}>
                            <div style={styles.consoleHeader}>
                                <Activity size={20} color="#667eea" />
                                <h3 style={styles.consoleTitle}>Doctor Console</h3>
                            </div>

                            {/* Clinical Notes */}
                            <div style={styles.section}>
                                <label style={styles.label}>
                                    <FileText size={16} />
                                    <span>Clinical Notes</span>
                                </label>
                                <textarea 
                                    value={notes}
                                    onChange={(e) => setNotes(e.target.value)}
                                    placeholder="Diagnosis, symptoms, observations..."
                                    style={styles.textarea}
                                />
                            </div>

                            {/* E-Prescription */}
                            <div style={styles.section}>
                                <div style={styles.labelRow}>
                                    <label style={styles.label}>
                                        <span>💊 E-Prescription</span>
                                    </label>
                                    <button onClick={addMedicine} style={styles.addButton}>
                                        <Plus size={14} />
                                        Add
                                    </button>
                                </div>

                                <div style={styles.medicineList}>
                                    {medicines.map((med, idx) => (
                                        <div key={idx} style={styles.medicineRow}>
                                            <input 
                                                type="text"
                                                placeholder="Medicine name"
                                                value={med.name}
                                                onChange={(e) => updateMedicine(idx, 'name', e.target.value)}
                                                style={styles.inputSmall}
                                            />
                                            <input 
                                                type="text"
                                                placeholder="Dosage"
                                                value={med.dosage}
                                                onChange={(e) => updateMedicine(idx, 'dosage', e.target.value)}
                                                style={styles.inputSmall}
                                            />
                                            <input 
                                                type="text"
                                                placeholder="Duration"
                                                value={med.duration}
                                                onChange={(e) => updateMedicine(idx, 'duration', e.target.value)}
                                                style={styles.inputTiny}
                                            />
                                            <button 
                                                onClick={() => removeMedicine(idx)}
                                                style={styles.deleteButton}
                                            >
                                                <Trash2 size={14} />
                                            </button>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Save Button */}
                            <button 
                                onClick={handleSave}
                                disabled={saving}
                                style={{
                                    ...styles.saveButton,
                                    background: saveStatus === 'success' ? '#10b981' : '#667eea',
                                    opacity: saving ? 0.7 : 1,
                                }}
                            >
                                {saveStatus === 'success' ? (
                                    <>
                                        <CheckCircle size={20} />
                                        <span>Saved Successfully!</span>
                                    </>
                                ) : (
                                    <>
                                        <Save size={20} />
                                        <span>{saving ? 'Saving...' : 'Save Consultation'}</span>
                                    </>
                                )}
                            </button>

                            {saveStatus === 'error' && (
                                <div style={styles.errorAlert}>
                                    <AlertCircle size={16} />
                                    <span>Failed to save. Please try again.</span>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>

            {/* End Call Confirmation Modal */}
            {showEndConfirm && (
                <div style={styles.modal}>
                    <div style={styles.modalContent}>
                        <div style={styles.modalHeader}>
                            <h3 style={styles.modalTitle}>End Consultation?</h3>
                            <button 
                                onClick={() => setShowEndConfirm(false)}
                                style={styles.closeButton}
                            >
                                <X size={20} />
                            </button>
                        </div>
                        <p style={styles.modalText}>
                            Are you sure you want to end this video consultation? 
                            {userRole === 'doctor' && ' Make sure you have saved all notes and prescriptions.'}
                        </p>
                        <div style={styles.modalActions}>
                            <button 
                                onClick={() => setShowEndConfirm(false)}
                                style={styles.cancelButton}
                            >
                                Cancel
                            </button>
                            <button 
                                onClick={endCall}
                                style={styles.confirmButton}
                            >
                                End Call
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

const styles = {
    container: {
        display: 'flex',
        height: '100vh',
        width: '100vw',
        background: '#0f172a',
        overflow: 'hidden',
    },
    videoSection: {
        flex: 1,
        display: 'flex',
        flexDirection: 'column',
        background: '#000',
    },
    header: {
        background: 'rgba(15, 23, 42, 0.95)',
        backdropFilter: 'blur(10px)',
        padding: '16px 24px',
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        borderBottom: '1px solid rgba(255,255,255,0.1)',
    },
    headerLeft: {
        display: 'flex',
        alignItems: 'center',
        gap: '12px',
    },
    logoSmall: {
        fontSize: '24px',
    },
    headerTitle: {
        color: '#fff',
        margin: 0,
        fontSize: '16px',
        fontWeight: '600',
    },
    headerSubtitle: {
        color: '#94a3b8',
        margin: 0,
        fontSize: '13px',
    },
    headerRight: {
        display: 'flex',
        alignItems: 'center',
        gap: '16px',
    },
    timer: {
        display: 'flex',
        alignItems: 'center',
        gap: '8px',
        color: '#fff',
        fontSize: '14px',
        fontWeight: '600',
        background: 'rgba(255,255,255,0.1)',
        padding: '8px 16px',
        borderRadius: '8px',
    },
    statusBadge: {
        display: 'flex',
        alignItems: 'center',
        gap: '8px',
        background: '#ef4444',
        color: '#fff',
        padding: '6px 14px',
        borderRadius: '20px',
        fontSize: '13px',
        fontWeight: '600',
    },
    statusDot: {
        width: '8px',
        height: '8px',
        borderRadius: '50%',
        background: '#fff',
        animation: 'pulse 2s infinite',
    },
    videoContainer: {
        flex: 1,
        position: 'relative',
    },
    videoLoading: {
        position: 'absolute',
        inset: 0,
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        color: '#e2e8f0',
        fontSize: '14px',
        background: 'rgba(0,0,0,0.5)',
        zIndex: 2,
    },
    controlsBar: {
        background: 'rgba(15, 23, 42, 0.95)',
        backdropFilter: 'blur(10px)',
        padding: '20px',
        display: 'flex',
        justifyContent: 'center',
        borderTop: '1px solid rgba(255,255,255,0.1)',
    },
    controlsGroup: {
        display: 'flex',
        gap: '12px',
    },
    controlButton: {
        padding: '14px 20px',
        borderRadius: '12px',
        border: 'none',
        color: '#fff',
        cursor: 'pointer',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        gap: '8px',
        transition: 'all 0.2s',
        fontSize: '14px',
        fontWeight: '600',
    },
    controlDisabled: {
        opacity: 0.6,
        cursor: 'not-allowed',
    },
    sidebar: {
        width: '380px',
        background: '#f8fafc',
        display: 'flex',
        flexDirection: 'column',
        borderLeft: '1px solid #e2e8f0',
    },
    tabs: {
        display: 'flex',
        background: '#fff',
        borderBottom: '2px solid #e2e8f0',
    },
    tab: {
        flex: 1,
        padding: '14px',
        border: 'none',
        background: 'transparent',
        cursor: 'pointer',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        gap: '8px',
        color: '#64748b',
        fontSize: '14px',
        fontWeight: '500',
        borderBottom: '2px solid transparent',
        transition: 'all 0.2s',
    },
    tabActive: {
        color: '#667eea',
        borderBottomColor: '#667eea',
    },
    tabContent: {
        flex: 1,
        overflow: 'auto',
        padding: '20px',
    },
    filesContainer: {
        display: 'flex',
        flexDirection: 'column',
        gap: '16px',
        height: '100%',
    },
    filesHeader: {
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'space-between',
        gap: '12px',
    },
    uploadButton: {
        display: 'inline-flex',
        alignItems: 'center',
        gap: '8px',
        padding: '10px 14px',
        background: '#e0e7ff',
        color: '#4338ca',
        borderRadius: '8px',
        cursor: 'pointer',
        fontSize: '14px',
        fontWeight: '600',
    },
    fileInput: {
        display: 'none',
    },
    refreshButton: {
        padding: '8px 12px',
        background: '#f1f5f9',
        color: '#475569',
        border: 'none',
        borderRadius: '8px',
        fontSize: '13px',
        fontWeight: '600',
        cursor: 'pointer',
    },
    fileList: {
        display: 'flex',
        flexDirection: 'column',
        gap: '10px',
    },
    fileRow: {
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'space-between',
        background: '#fff',
        padding: '12px',
        borderRadius: '8px',
        boxShadow: '0 1px 3px rgba(0,0,0,0.1)',
    },
    fileName: {
        fontWeight: '600',
        color: '#1e293b',
        fontSize: '14px',
    },
    fileMeta: {
        color: '#94a3b8',
        fontSize: '12px',
        marginTop: '4px',
    },
    fileLink: {
        color: '#4338ca',
        textDecoration: 'none',
        fontWeight: '600',
        fontSize: '13px',
    },
    chatContainer: {
        display: 'flex',
        flexDirection: 'column',
        height: '100%',
    },
    chatMessages: {
        flex: 1,
        overflowY: 'auto',
        marginBottom: '16px',
    },
    emptyState: {
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        justifyContent: 'center',
        height: '100%',
        color: '#94a3b8',
    },
    emptyText: {
        marginTop: '12px',
        fontSize: '14px',
    },
    chatMessage: {
        background: '#fff',
        padding: '12px',
        borderRadius: '8px',
        marginBottom: '12px',
        boxShadow: '0 1px 3px rgba(0,0,0,0.1)',
    },
    chatHeader: {
        display: 'flex',
        justifyContent: 'space-between',
        marginBottom: '6px',
    },
    chatSender: {
        color: '#1e293b',
        fontSize: '13px',
    },
    chatTime: {
        color: '#94a3b8',
        fontSize: '11px',
    },
    chatText: {
        margin: 0,
        color: '#475569',
        fontSize: '14px',
    },
    chatInput: {
        display: 'flex',
        gap: '8px',
    },
    input: {
        flex: 1,
        padding: '10px 14px',
        border: '2px solid #e2e8f0',
        borderRadius: '8px',
        fontSize: '14px',
        outline: 'none',
    },
    sendButton: {
        padding: '10px 20px',
        background: '#667eea',
        color: '#fff',
        border: 'none',
        borderRadius: '8px',
        fontSize: '14px',
        fontWeight: '600',
        cursor: 'pointer',
    },
    participantsContainer: {
        display: 'flex',
        flexDirection: 'column',
        gap: '12px',
    },
    participant: {
        display: 'flex',
        alignItems: 'center',
        gap: '12px',
        background: '#fff',
        padding: '14px',
        borderRadius: '10px',
        boxShadow: '0 1px 3px rgba(0,0,0,0.1)',
    },
    participantAvatar: {
        width: '48px',
        height: '48px',
        borderRadius: '50%',
        background: 'linear-gradient(135deg, #667eea, #764ba2)',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        fontSize: '24px',
    },
    participantInfo: {
        flex: 1,
    },
    participantName: {
        fontWeight: '600',
        color: '#1e293b',
        fontSize: '14px',
    },
    participantStatus: {
        color: '#10b981',
        fontSize: '12px',
        marginTop: '2px',
    },
    consoleContainer: {
        display: 'flex',
        flexDirection: 'column',
        gap: '20px',
    },
    consoleHeader: {
        display: 'flex',
        alignItems: 'center',
        gap: '10px',
    },
    consoleTitle: {
        margin: 0,
        fontSize: '18px',
        fontWeight: '700',
        color: '#1e293b',
    },
    section: {
        background: '#fff',
        padding: '16px',
        borderRadius: '10px',
        boxShadow: '0 1px 3px rgba(0,0,0,0.1)',
    },
    label: {
        display: 'flex',
        alignItems: 'center',
        gap: '8px',
        fontWeight: '600',
        color: '#475569',
        fontSize: '14px',
        marginBottom: '10px',
    },
    labelRow: {
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
    },
    textarea: {
        width: '100%',
        minHeight: '100px',
        padding: '10px',
        border: '2px solid #e2e8f0',
        borderRadius: '8px',
        fontSize: '14px',
        resize: 'vertical',
        outline: 'none',
        fontFamily: 'inherit',
    },
    addButton: {
        display: 'flex',
        alignItems: 'center',
        gap: '4px',
        padding: '6px 12px',
        background: '#e0e7ff',
        color: '#4338ca',
        border: 'none',
        borderRadius: '6px',
        fontSize: '13px',
        fontWeight: '600',
        cursor: 'pointer',
    },
    medicineList: {
        display: 'flex',
        flexDirection: 'column',
        gap: '8px',
    },
    medicineRow: {
        display: 'flex',
        gap: '6px',
        alignItems: 'center',
    },
    inputSmall: {
        flex: 2,
        padding: '8px',
        border: '1px solid #e2e8f0',
        borderRadius: '6px',
        fontSize: '13px',
        outline: 'none',
    },
    inputTiny: {
        flex: 1,
        padding: '8px',
        border: '1px solid #e2e8f0',
        borderRadius: '6px',
        fontSize: '13px',
        outline: 'none',
    },
    deleteButton: {
        padding: '8px',
        background: 'transparent',
        border: 'none',
        color: '#ef4444',
        cursor: 'pointer',
    },
    saveButton: {
        width: '100%',
        padding: '14px',
        border: 'none',
        borderRadius: '10px',
        color: '#fff',
        fontSize: '15px',
        fontWeight: '700',
        cursor: 'pointer',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        gap: '10px',
        transition: 'all 0.3s',
    },
    errorAlert: {
        display: 'flex',
        alignItems: 'center',
        gap: '8px',
        padding: '10px',
        background: '#fee2e2',
        color: '#991b1b',
        borderRadius: '8px',
        fontSize: '13px',
    },
    modal: {
        position: 'fixed',
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
        background: 'rgba(0,0,0,0.7)',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        zIndex: 1000,
    },
    modalContent: {
        background: '#fff',
        borderRadius: '16px',
        padding: '24px',
        maxWidth: '400px',
        width: '90%',
    },
    modalHeader: {
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: '16px',
    },
    modalTitle: {
        margin: 0,
        fontSize: '20px',
        fontWeight: '700',
        color: '#1e293b',
    },
    closeButton: {
        background: 'transparent',
        border: 'none',
        cursor: 'pointer',
        color: '#64748b',
    },
    modalText: {
        color: '#475569',
        fontSize: '14px',
        lineHeight: '1.6',
        marginBottom: '24px',
    },
    modalActions: {
        display: 'flex',
        gap: '12px',
        justifyContent: 'flex-end',
    },
    cancelButton: {
        padding: '10px 20px',
        background: '#f1f5f9',
        color: '#475569',
        border: 'none',
        borderRadius: '8px',
        fontSize: '14px',
        fontWeight: '600',
        cursor: 'pointer',
    },
    confirmButton: {
        padding: '10px 20px',
        background: '#ef4444',
        color: '#fff',
        border: 'none',
        borderRadius: '8px',
        fontSize: '14px',
        fontWeight: '600',
        cursor: 'pointer',
    },
};

export default VideoRoom;
