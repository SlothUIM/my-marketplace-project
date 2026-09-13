<!-- post.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>List an Item - COMMON.</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .form-box { max-width: 500px; margin: 0 auto; background: var(--surface); border: 1px solid var(--border); padding: 2rem; border-radius: 8px; }
        .drop-box { border: 2px dashed #ccc; padding: 2.5rem 1rem; text-align: center; background: #fafafa; border-radius: 6px; cursor: pointer; margin-bottom: 1.5rem; }
        .field { display: flex; flex-direction: column; gap: 0.4rem; margin-bottom: 1.25rem; }
        input, textarea { padding: 0.7rem; border: 1px solid var(--border); border-radius: 4px; font-size: 1rem; }
        .secure-note { font-size: 0.75rem; color: var(--accent-green); font-weight: 600; margin-top: 0.2rem; }
    </style>
</head>
<body>
    <header>
        <!-- Extensionless path target pointing to the base root -->
        <a href="./" class="logo-btn"><h1>COMMON.</h1></a>
    </header>
    <main class="form-box">
        <h3 style="margin-top:0;">Frictionless Posting Flow</h3>
        
        <div class="drop-box" id="dropZone">
            <span>📸 <strong>Drag item photos here</strong> or click to browse</span>
        </div>

        <!-- HOOKED UP BACKEND PROCESSOR TARGET LINK AND ENCTYPE MULTIPART -->
        <form action="publish" method="POST" enctype="multipart/form-data">
            
            <div class="field">
                <label for="title">What are you selling?</label>
                <input type="text" id="title" name="title" placeholder="e.g., DeWalt Drill, Wooden Coffee Table" onkeyup="predictCategory(this.value)" required>
            </div>

            <div class="field">
                <label for="categorySelect">Category Selection</label>
                <select id="categorySelect" name="category" required style="padding: 0.7rem; border: 1px solid var(--border); border-radius: 4px; font-size: 1rem; color: var(--text); background: var(--surface);">
                    <option value="" disabled selected>Select category...</option>
                    <option value="vehicles">🚗 Vehicles</option>
                    <option value="parts">⚙️ Auto Parts</option>
                    <option value="tools">🔧 Tools</option>
                    <option value="garden">🌱 Garden & Outdoor</option>
                    <option value="furniture">🛋️ Furniture</option>
                    <option value="appliances">🔌 Appliances</option>
                    <option value="electronics">💻 Tech & Computing</option>
                    <option value="phones">📱 Mobile Phones</option>
                    <option value="sporting">🚲 Sports & Bikes</option>
                    <option value="hobbies">🎨 Hobbies & Art</option>
                    <option value="books">📚 Books & Media</option>
                    <option value="free">🎁 Free Curb Alerts</option>
                </select>
                <span id="predictFeedback" style="font-size: 0.75rem; color: var(--accent-indigo); font-weight: 600; margin-top: 0.25rem; display: block; height: 1em;"></span>
            </div>

            <div class="field" style="display:flex; flex-direction:row; gap:1rem;">
                <div style="flex:1;">
                    <label>Price ($)</label>
                    <input type="number" name="price" placeholder="0" required>
                </div>
                <div style="flex:1;">
                    <label>ZIP Code</label>
                    <input type="number" name="zip" placeholder="54481" required>
                </div>
            </div>
            
            <div class="field">
                <label>Description</label>
                <textarea name="description" rows="3" placeholder="Provide raw condition details, flaws, or meetup availability specs..."></textarea>
            </div>
            
            <div class="field">
                <label>Your Endpoint (Email/SMS)</label>
                <input type="text" name="endpoint" placeholder="For hidden buyer routing alerts" required>
                <span class="secure-note">🔒 Layer Masked Natively</span>
            </div>
            
            <div class="field">
                <label>Digital P2P Acceptance (Optional)</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 0.25rem;">
                    <div>
                        <input type="text" name="venmo" placeholder="Venmo @username" style="width: 100%; box-sizing: border-box;">
                        <span class="secure-note">🔒 Masked Layer Activated</span>
                    </div>
                    <div>
                        <input type="text" name="cashapp" placeholder="Cash App $cashtag" style="width: 100%; box-sizing: border-box;">
                        <span class="secure-note">🔒 Masked Layer Activated</span>
                    </div>
                </div>
                <span style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-top: 0.5rem; line-height: 1.3;">
                    Your handles are permanently encrypted from the public web. Tapping the payment links during an in-person meetup launches the buyer's native mobile banking app securely.
                </span>
            </div>

            <button type="submit" class="btn" style="width:100%; padding:0.8rem; font-size:1rem;">Publish (Get Magic Link via email)</button>
        </form>
    </main>

    <script>
        const keywordsMap = {
            "vehicles": ["car", "truck", "suv", "honda", "ford", "chevy", "toyota", "jeep", "sedan"],
            "parts": ["tire", "rim", "brake", "alternator", "bumper", "engine", "exhaust", "battery"],
            "tools": ["drill", "saw", "wrench", "craftsman", "dewalt", "milwaukee", "toolbox", "hammer"],
            "garden": ["lawnmower", "mower", "hose", "plants", "soil", "rake", "weeder", "patio", "shrub"],
            "furniture": ["couch", "sofa", "table", "chair", "bed", "dresser", "desk", "ottoman", "futon"],
            "electronics": ["computer", "laptop", "monitor", "gpu", "desktop", "tv", "camera", "hdmi"],
            "phones": ["iphone", "samsung", "android", "galaxy", "pixel", "charger", "smartphone"]
        };

        function predictCategory(text) {
            const query = text.toLowerCase().trim();
            const select = document.getElementById('categorySelect');
            const feedback = document.getElementById('predictFeedback');
            if (!select || !feedback) return;

            if (query.length < 3) {
                feedback.innerHTML = "";
                return;
            }

            for (const [category, keywords] of Object.entries(keywordsMap)) {
                const hasMatch = keywords.some(keyword => query.includes(keyword));
                
                if (hasMatch) {
                    select.value = category;
                    feedback.innerHTML = `✨ Auto-detected matching category! Feel free to modify if incorrect.`;
                    return;
                }
            }
            feedback.innerHTML = "";
        }

        const zone = document.getElementById('dropZone');
        zone.addEventListener('dragover', (e) => { e.preventDefault(); zone.style.borderColor = 'var(--accent-green)'; });
        zone.addEventListener('dragleave', () => { zone.style.borderColor = '#ccc'; });
    </script>

    <script>
        const dropZoneEl = document.getElementById('dropZone');
        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.multiple = true;
        fileInput.accept = 'image/*';
        fileInput.name = 'images[]'; 
        fileInput.style.display = 'none';
        const targetForm = document.querySelector('form[action="publish"]');
		if (targetForm) {
			targetForm.appendChild(fileInput);
		} else {
			document.body.appendChild(fileInput); // Safe fallback
		}

        const previewGrid = document.createElement('div');
        previewGrid.style.display = 'grid';
        previewGrid.style.gridTemplateColumns = 'repeat(auto-fill, minmax(80px, 1fr))';
        previewGrid.style.gap = '10px';
        previewGrid.style.marginTop = '1rem';
        dropZoneEl.parentNode.insertBefore(previewGrid, dropZoneEl.nextSibling);

        // FIXED AND FULLY CLOSING EVENT HANDLERS
        dropZoneEl.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', handleFiles);

        dropZoneEl.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZoneEl.style.borderColor = 'var(--accent-green)';
            dropZoneEl.style.background = '#f0fdf4';
        });

        dropZoneEl.addEventListener('dragleave', () => {
            dropZoneEl.style.borderColor = '#ccc';
            dropZoneEl.style.background = '#fafafa';
        });

        dropZoneEl.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZoneEl.style.borderColor = '#ccc';
            dropZoneEl.style.background = '#fafafa';
            
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                handleFiles();
            }
        });

        function handleFiles() {
            previewGrid.innerHTML = ''; 
            const files = Array.from(fileInput.files);

            files.forEach(file => {
                if (!file.type.startsWith('image/')) return;

                const reader = new FileReader();
                reader.onload = (e) => {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.width = '100%';
                    img.style.aspectRatio = '4/3';
                    img.style.objectFit = 'cover';
                    img.style.borderRadius = '4px';
                    img.style.border = '1px solid var(--border)';
                    previewGrid.appendChild(img);
                };
                reader.readAsDataURL(file);
            });

            dropZoneEl.innerHTML = `<span>✅ <strong>${files.length} Photo(s) Attached</strong></span>`;
        }
    </script>
</body>
</html>
