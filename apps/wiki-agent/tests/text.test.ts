import test from 'node:test';
import assert from 'node:assert/strict';
import { createAdminSession, verifyAdminSession } from '../src/adminAuth.js';
import { excerpt, parseTags, slugify, stripHtml } from '../src/text.js';

test('slugify normalizes spaces and symbols', () => {
  assert.equal(slugify('AI Community Wiki!!!'), 'ai-community-wiki');
});

test('stripHtml removes tags and scripts', () => {
  assert.equal(stripHtml('<h1>Hello</h1><script>alert(1)</script><p>world</p>'), 'Hello world');
});

test('excerpt trims long text', () => {
  assert.equal(excerpt('x'.repeat(300), 20), 'xxxxxxxxxxxxxxxxxxx…');
});

test('parseTags returns normalized tags', () => {
  assert.deepEqual(parseTags('ai, wiki,  agents  ,'), ['ai', 'wiki', 'agents']);
});

test('admin session token verifies only for expected user', () => {
  const token = createAdminSession('admin', 'secret');
  assert.equal(verifyAdminSession(token, 'secret', 'admin'), true);
  assert.equal(verifyAdminSession(token, 'secret', 'other'), false);
});
