import crypto from 'node:crypto';

const SESSION_TTL_SECONDS = 60 * 60 * 12;

function signValue(value: string, secret: string): string {
  return crypto.createHmac('sha256', secret).update(value).digest('hex');
}

export function createAdminSession(username: string, secret: string): string {
  const expiresAt = Math.floor(Date.now() / 1000) + SESSION_TTL_SECONDS;
  const payload = `${username}:${expiresAt}`;
  const signature = signValue(payload, secret);

  return Buffer.from(`${payload}:${signature}`, 'utf8').toString('base64url');
}

export function verifyAdminSession(token: string | undefined, secret: string, expectedUsername: string): boolean {
  if (!token) {
    return false;
  }

  try {
    const decoded = Buffer.from(token, 'base64url').toString('utf8');
    const [username, expiresAtRaw, signature] = decoded.split(':');
    if (!username || !expiresAtRaw || !signature) {
      return false;
    }

    if (username !== expectedUsername) {
      return false;
    }

    const expiresAt = Number.parseInt(expiresAtRaw, 10);
    if (Number.isNaN(expiresAt) || expiresAt < Math.floor(Date.now() / 1000)) {
      return false;
    }

    const payload = `${username}:${expiresAt}`;
    const expectedSignature = signValue(payload, secret);

    return crypto.timingSafeEqual(Buffer.from(signature), Buffer.from(expectedSignature));
  } catch {
    return false;
  }
}
