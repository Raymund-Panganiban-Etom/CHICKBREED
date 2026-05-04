<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Plus Box Task List (Local)</title>
<style>
  :root{--accent:#0b78d1;--muted:#666;--bg:#f6f8fb}
  body{font-family:Inter,Segoe UI,Arial; background:var(--bg); margin:0; padding:28px; display:flex;flex-direction:column;align-items:center;}
  .container{width:720px; max-width:96%;}
  .plus-box{width:84px;height:84px;border-radius:12px;background:var(--accent);color:#fff;font-size:56px;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 6px 18px rgba(11,120,209,.18);user-select:none;}
  .plus-box:hover{transform:translateY(-3px);transition:transform .15s}
  .form-card{margin-top:18px;background:#fff;padding:16px;border-radius:10px;box-shadow:0 6px 18px rgba(2,6,23,.06);display:none;}
  label{display:block;font-weight:600;margin-top:8px;color:#222}
  input[type="text"], input[type="tel"], textarea{width:100%;padding:8px;border:1px solid #e2e6ef;border-radius:6px;margin-top:6px;font-size:14px}
  textarea{min-height:72px;resize:vertical}
  .row{display:flex;gap:8px}
  .row > *{flex:1}
  .small{font-size:13px;color:var(--muted);margin-top:6px}
  .btn{background:var(--accent);color:#fff;padding:10px 14px;border-radius:8px;border:0;cursor:pointer;margin-top:12px}
  .btn.secondary{background:#e9eef8;color:var(--accent);border:1px solid rgba(11,120,209,.12)}
  #list{margin-top:18px;display:flex;flex-direction:column;gap:10px}
  .item{background:#fff;padding:12px;border-radius:10px;display:flex;gap:12px;align-items:flex-start;box-shadow:0 6px 18px rgba(2,6,23,.04)}
  .thumb{width:96px;height:72px;border-radius:6px;background:#f0f2f6;object-fit:cover;border:1px solid #eee}
  .meta{flex:1}
  .meta h4{margin:0 0 6px 0;font-size:16px}
  .meta p{margin:0;color:var(--muted);font-size:13px}
  .actions{display:flex;flex-direction:column;gap:6px}
  .remove{background:#ff6b6b;color:#fff;border:0;padding:6px 8px;border-radius:6px;cursor:pointer}
  /* consent modal */
  .modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(0,0,0,.45);z-index:40}
  .modal-card{width:520px;max-width:94%;background:#fff;padding:18px;border-radius:10px}
  .consent-text{font-size:14px;color:#222;line-height:1.4}
  .consent-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:12px}
  .consent-copy{font-size:12px;color:var(--muted);margin-top:8px}
  @media (max-width:560px){ .row{flex-direction:column} .thumb{width:72px;height:56px} }
</style>
</head>
<body>
  <div class="container">
    <div style="display:flex;align-items:center;gap:14px;">
      <div class="plus-box" id="plusBox" title="Add new item">+</div>
      <div>
        <h2 style="margin:0">Your Items</h2>
        <div class="small">Click the plus to add a new entry. Entries are stored locally on this device.</div>
      </div>
    </div>

    <div class="form-card" id="formCard" aria-hidden="true">
      <form id="entryForm">
        <label>Description <span style="font-weight:400;color:var(--muted)">(what you want to record)</span></label>
        <textarea name="description" id="description" required></textarea>

        <div class="row">
          <div>
            <label>Photo <span style="font-weight:400;color:var(--muted)">(optional)</span></label>
            <input type="file" id="photo" accept="image/*">
            <div class="small">Small images recommended; large files may not persist in some browsers.</div>
          </div>
          <div>
            <label>Social Media</label>
            <input type="text" id="socmed" placeholder="@username or profile link">
            <label style="margin-top:8px">Number</label>
            <input type="tel" id="number" placeholder="+63...">
          </div>
        </div>

        <label>Location</label>
        <input type="text" id="location" placeholder="Type a location or address">

        <div style="display:flex;gap:8px;align-items:center;">
          <button type="button" class="btn" id="submitBtn">Submit</button>
          <button type="button" class="btn secondary" id="cancelBtn">Cancel</button>
        </div>
      </form>
    </div>

    <div id="list" aria-live="polite"></div>
  </div>

  <!-- Consent modal -->
  <div class="modal" id="consentModal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="modal-card">
      <h3 style="margin-top:0">Consent under Republic Act No. 10173</h3>
      <div class="consent-text" id="consentText">
        By clicking <strong>Accept</strong> you give your explicit consent to the collection and processing of the personal data you provided (including photo and location) for the purpose of recording this entry. This consent is recorded in accordance with the Data Privacy Act of 2012 (Republic Act No. 10173). You may request access, correction, or deletion of your data; this demo stores data locally on your device only.
      </div>
      <label style="display:block;margin-top:10px"><input type="checkbox" id="consentCheck"> I have read and accept the terms and privacy statement above</label>
      <div class="consent-actions">
        <button class="btn secondary" id="consentCancel">Cancel</button>
        <button class="btn" id="consentAccept">Accept</button>
      </div>
      <div class="consent-copy">A copy of this consent text will be saved with the entry locally.</div>
    </div>
  </div>

<script>
/* Simple local-only implementation:
   - stores entries in localStorage under key "local_entries"
   - photos are saved as base64 (data URLs) — be mindful of size limits
*/

const plusBox = document.getElementById('plusBox');
const formCard = document.getElementById('formCard');
const entryForm = document.getElementById('entryForm');
const submitBtn = document.getElementById('submitBtn');
const cancelBtn = document.getElementById('cancelBtn');
const listEl = document.getElementById('list');
const consentModal = document.getElementById('consentModal');
const consentCheck = document.getElementById('consentCheck');
const consentAccept = document.getElementById('consentAccept');
const consentCancel = document.getElementById('consentCancel');
const consentText = document.getElementById('consentText');

const STORAGE_KEY = 'local_entries_v1';

// toggle form
plusBox.addEventListener('click', () => {
  formCard.style.display = formCard.style.display === 'block' ? 'none' : 'block';
  formCard.setAttribute('aria-hidden', formCard.style.display !== 'block');
});

// cancel clears form and hides
cancelBtn.addEventListener('click', () => {
  entryForm.reset();
  formCard.style.display = 'none';
});

// helper: load entries
function loadEntries(){
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    return raw ? JSON.parse(raw) : [];
  } catch(e){ return []; }
}

// helper: save entries
function saveEntries(arr){
  localStorage.setItem(STORAGE_KEY, JSON.stringify(arr));
}

// render list
function renderList(){
  const items = loadEntries();
  listEl.innerHTML = '';
  if(items.length === 0){
    listEl.innerHTML = '<div class="small" style="padding:12px;background:#fff;border-radius:8px;margin-top:12px">No entries yet. Add one with the plus box.</div>';
    return;
  }
  items.forEach((it, idx) => {
    const item = document.createElement('div'); item.className = 'item';
    const img = document.createElement('img'); img.className = 'thumb';
    img.src = it.photo || 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="96" height="72"><rect width="100%" height="100%" fill="%23f0f2f6"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="%23b0b6c3" font-size="12">No photo</text></svg>';
    const meta = document.createElement('div'); meta.className = 'meta';
    const h = document.createElement('h4'); h.textContent = it.description || '(no description)';
    const p1 = document.createElement('p'); p1.innerHTML = `<strong>Social</strong>: ${escapeHtml(it.socmed || '-')}`;
    const p2 = document.createElement('p'); p2.innerHTML = `<strong>Number</strong>: ${escapeHtml(it.number || '-')}`;
    const p3 = document.createElement('p'); p3.innerHTML = `<strong>Location</strong>: ${escapeHtml(it.location || '-')}`;
    const p4 = document.createElement('p'); p4.innerHTML = `<em style="color:var(--muted)">Consent recorded</em>`;
    meta.appendChild(h); meta.appendChild(p1); meta.appendChild(p2); meta.appendChild(p3); meta.appendChild(p4);

    const actions = document.createElement('div'); actions.className = 'actions';
    const remove = document.createElement('button'); remove.className = 'remove'; remove.textContent = 'Remove';
    remove.addEventListener('click', () => {
      if(!confirm('Remove this entry?')) return;
      const arr = loadEntries();
      arr.splice(idx,1);
      saveEntries(arr);
      renderList();
    });
    actions.appendChild(remove);

    item.appendChild(img);
    item.appendChild(meta);
    item.appendChild(actions);
    listEl.appendChild(item);
  });
}

// escape helper
function escapeHtml(s){ return String(s||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

// when user clicks Submit -> open consent modal (but first validate)
submitBtn.addEventListener('click', async () => {
  const desc = document.getElementById('description').value.trim();
  if(!desc){ alert('Please enter a description.'); return; }
  // ensure at least description present; other fields optional
  consentCheck.checked = false;
  consentModal.style.display = 'flex';
  consentModal.setAttribute('aria-hidden','false');
});

// consent cancel
consentCancel.addEventListener('click', () => {
  consentModal.style.display = 'none';
  consentModal.setAttribute('aria-hidden','true');
});

// consent accept -> gather form, save to localStorage, render
consentAccept.addEventListener('click', async () => {
  if(!consentCheck.checked){ alert('Please check the consent box to proceed.'); return; }
  // gather fields
  const description = document.getElementById('description').value.trim();
  const socmed = document.getElementById('socmed').value.trim();
  const number = document.getElementById('number').value.trim();
  const location = document.getElementById('location').value.trim();
  const photoInput = document.getElementById('photo');

  // read photo as data URL if present
  let photoData = null;
  if(photoInput.files && photoInput.files[0]){
    try {
      photoData = await readFileAsDataURL(photoInput.files[0]);
    } catch(e){
      console.warn('Photo read failed', e);
      photoData = null;
    }
  }

  // build entry with consent metadata
  const entry = {
    description,
    socmed,
    number,
    location,
    photo: photoData,
    consent_text: consentText.innerText,
    consent_ts: new Date().toISOString(),
    user_agent: navigator.userAgent || '',
    saved_at: new Date().toISOString()
  };

  const arr = loadEntries();
  arr.unshift(entry); // newest first
  saveEntries(arr);

  // UI updates
  consentModal.style.display = 'none';
  consentModal.setAttribute('aria-hidden','true');
  entryForm.reset();
  formCard.style.display = 'none';
  renderList();
  // brief confirmation
  alert('Entry saved locally. You can remove it anytime.');
});

// utility: read file as data URL
function readFileAsDataURL(file){
  return new Promise((resolve,reject)=>{
    const fr = new FileReader();
    fr.onload = ()=> resolve(fr.result);
    fr.onerror = ()=> reject(fr.error);
    fr.readAsDataURL(file);
  });
}

// initial render
renderList();
</script>
</body>
</html>
