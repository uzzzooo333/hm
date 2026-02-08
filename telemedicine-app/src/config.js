const DEFAULT_API_BASE = 'http://localhost/mediconnect360/api';

export const API_BASE =
  import.meta.env?.VITE_API_BASE || DEFAULT_API_BASE;

export const JITSI_DOMAIN =
  import.meta.env?.VITE_JITSI_DOMAIN || null;
