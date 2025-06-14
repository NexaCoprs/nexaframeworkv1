"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.ColorPalette = void 0;
const vscode = require("vscode");
const fs = require("fs");
const path = require("path");
class ColorPalette {
    constructor(context) {
        this.palettes = new Map();
        this.context = context;
        this.loadPalettes();
    }
    async createPalette() {
        const paletteName = await vscode.window.showInputBox({
            prompt: 'Nom de la nouvelle palette',
            placeHolder: 'Ex: Palette Océan'
        });
        if (!paletteName) {
            return;
        }
        const paletteType = await vscode.window.showQuickPick([
            'Monochrome',
            'Analogique',
            'Complémentaire',
            'Triadique',
            'Tétradique',
            'Personnalisée'
        ], {
            placeHolder: 'Type de palette de couleurs'
        });
        if (!paletteType) {
            return;
        }
        let colors = [];
        if (paletteType === 'Personnalisée') {
            colors = await this.createCustomPalette();
        }
        else {
            const baseColor = await vscode.window.showInputBox({
                prompt: 'Couleur de base (format hex)',
                placeHolder: '#3498db',
                validateInput: (value) => {
                    if (!/^#[0-9A-Fa-f]{6}$/.test(value)) {
                        return 'Format invalide. Utilisez le format #RRGGBB';
                    }
                    return null;
                }
            });
            if (!baseColor) {
                return;
            }
            colors = this.generatePalette(baseColor, paletteType);
        }
        const palette = {
            name: paletteName,
            type: paletteType,
            colors: colors,
            createdAt: new Date().toISOString(),
            description: `Palette ${paletteType.toLowerCase()} créée avec Nexa Theme Designer`
        };
        this.palettes.set(paletteName, palette);
        await this.savePalettes();
        vscode.window.showInformationMessage(`Palette "${paletteName}" créée avec succès!`);
        await this.showPalettePreview(paletteName);
    }
    async editPalette() {
        const paletteNames = Array.from(this.palettes.keys());
        if (paletteNames.length === 0) {
            const create = await vscode.window.showInformationMessage('Aucune palette trouvée. Voulez-vous en créer une ?', 'Créer', 'Annuler');
            if (create === 'Créer') {
                await this.createPalette();
            }
            return;
        }
        const selectedPalette = await vscode.window.showQuickPick(paletteNames, {
            placeHolder: 'Choisissez une palette à éditer'
        });
        if (selectedPalette) {
            await this.openPaletteEditor(selectedPalette);
        }
    }
    async deletePalette() {
        const paletteNames = Array.from(this.palettes.keys());
        if (paletteNames.length === 0) {
            vscode.window.showWarningMessage('Aucune palette à supprimer');
            return;
        }
        const paletteToDelete = await vscode.window.showQuickPick(paletteNames, {
            placeHolder: 'Choisissez une palette à supprimer'
        });
        if (!paletteToDelete) {
            return;
        }
        const confirm = await vscode.window.showWarningMessage(`Êtes-vous sûr de vouloir supprimer la palette "${paletteToDelete}" ?`, 'Supprimer', 'Annuler');
        if (confirm === 'Supprimer') {
            this.palettes.delete(paletteToDelete);
            await this.savePalettes();
            vscode.window.showInformationMessage(`Palette "${paletteToDelete}" supprimée!`);
        }
    }
    async importPalette() {
        const importType = await vscode.window.showQuickPick([
            'Fichier JSON',
            'Adobe Swatch Exchange (.ase)',
            'GIMP Palette (.gpl)',
            'Photoshop Swatch (.aco)',
            'URL (Adobe Color, Coolors.co)'
        ], {
            placeHolder: 'Type d\'import'
        });
        if (!importType) {
            return;
        }
        switch (importType) {
            case 'Fichier JSON':
                await this.importFromJSON();
                break;
            case 'URL (Adobe Color, Coolors.co)':
                await this.importFromURL();
                break;
            default:
                vscode.window.showInformationMessage(`Import ${importType} sera disponible dans une prochaine version`);
                break;
        }
    }
    async exportPalette() {
        const paletteNames = Array.from(this.palettes.keys());
        if (paletteNames.length === 0) {
            vscode.window.showWarningMessage('Aucune palette à exporter');
            return;
        }
        const paletteToExport = await vscode.window.showQuickPick(paletteNames, {
            placeHolder: 'Choisissez une palette à exporter'
        });
        if (!paletteToExport) {
            return;
        }
        const exportFormat = await vscode.window.showQuickPick([
            'JSON',
            'CSS Variables',
            'SCSS Variables',
            'Adobe Swatch Exchange (.ase)',
            'GIMP Palette (.gpl)',
            'Photoshop Swatch (.aco)'
        ], {
            placeHolder: 'Format d\'export'
        });
        if (!exportFormat) {
            return;
        }
        const palette = this.palettes.get(paletteToExport);
        switch (exportFormat) {
            case 'JSON':
                await this.exportAsJSON(palette);
                break;
            case 'CSS Variables':
                await this.exportAsCSS(palette);
                break;
            case 'SCSS Variables':
                await this.exportAsSCSS(palette);
                break;
            default:
                vscode.window.showInformationMessage(`Export ${exportFormat} sera disponible dans une prochaine version`);
                break;
        }
    }
    async generateHarmony() {
        const baseColor = await vscode.window.showInputBox({
            prompt: 'Couleur de base (format hex)',
            placeHolder: '#3498db',
            validateInput: (value) => {
                if (!/^#[0-9A-Fa-f]{6}$/.test(value)) {
                    return 'Format invalide. Utilisez le format #RRGGBB';
                }
                return null;
            }
        });
        if (!baseColor) {
            return;
        }
        const harmonyType = await vscode.window.showQuickPick([
            'Monochrome',
            'Analogique',
            'Complémentaire',
            'Complémentaire divisée',
            'Triadique',
            'Tétradique',
            'Carré'
        ], {
            placeHolder: 'Type d\'harmonie colorielle'
        });
        if (!harmonyType) {
            return;
        }
        const colors = this.generateColorHarmony(baseColor, harmonyType);
        const paletteName = await vscode.window.showInputBox({
            prompt: 'Nom de la palette d\'harmonie',
            value: `Harmonie ${harmonyType} - ${baseColor}`
        });
        if (!paletteName) {
            return;
        }
        const palette = {
            name: paletteName,
            type: harmonyType,
            colors: colors,
            createdAt: new Date().toISOString(),
            description: `Harmonie ${harmonyType.toLowerCase()} basée sur ${baseColor}`
        };
        this.palettes.set(paletteName, palette);
        await this.savePalettes();
        vscode.window.showInformationMessage(`Palette d'harmonie "${paletteName}" créée!`);
        await this.showPalettePreview(paletteName);
    }
    async analyzeImage() {
        const fileUri = await vscode.window.showOpenDialog({
            canSelectFiles: true,
            canSelectFolders: false,
            canSelectMany: false,
            filters: {
                'Images': ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp'],
                'All Files': ['*']
            }
        });
        if (!fileUri || fileUri.length === 0) {
            return;
        }
        vscode.window.showInformationMessage('Analyse d\'image sera disponible dans une prochaine version');
        // TODO: Implémenter l'extraction de couleurs depuis une image
    }
    getPalettes() {
        return this.palettes;
    }
    getPalette(name) {
        return this.palettes.get(name);
    }
    async createCustomPalette() {
        const colors = [];
        let addMore = true;
        while (addMore && colors.length < 20) {
            const color = await vscode.window.showInputBox({
                prompt: `Couleur ${colors.length + 1} (format hex)`,
                placeHolder: '#3498db',
                validateInput: (value) => {
                    if (!/^#[0-9A-Fa-f]{6}$/.test(value)) {
                        return 'Format invalide. Utilisez le format #RRGGBB';
                    }
                    return null;
                }
            });
            if (!color) {
                break;
            }
            colors.push(color);
            if (colors.length >= 3) {
                const continueAdding = await vscode.window.showQuickPick([
                    'Ajouter une autre couleur',
                    'Terminer la palette'
                ], {
                    placeHolder: `${colors.length} couleurs ajoutées`
                });
                addMore = continueAdding === 'Ajouter une autre couleur';
            }
        }
        return colors;
    }
    generatePalette(baseColor, type) {
        const hsl = this.hexToHsl(baseColor);
        const colors = [baseColor];
        switch (type) {
            case 'Monochrome':
                return this.generateMonochrome(hsl);
            case 'Analogique':
                return this.generateAnalogous(hsl);
            case 'Complémentaire':
                return this.generateComplementary(hsl);
            case 'Triadique':
                return this.generateTriadic(hsl);
            case 'Tétradique':
                return this.generateTetradic(hsl);
            default:
                return colors;
        }
    }
    generateColorHarmony(baseColor, harmonyType) {
        return this.generatePalette(baseColor, harmonyType);
    }
    generateMonochrome(hsl) {
        const colors = [];
        const baseH = hsl.h;
        const baseS = hsl.s;
        // Générer différentes luminosités
        const lightnesses = [0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9];
        for (const l of lightnesses) {
            colors.push(this.hslToHex({ h: baseH, s: baseS, l }));
        }
        return colors;
    }
    generateAnalogous(hsl) {
        const colors = [];
        const baseH = hsl.h;
        // Couleurs analogues (±30° sur la roue chromatique)
        const hues = [baseH - 30, baseH - 15, baseH, baseH + 15, baseH + 30];
        for (const h of hues) {
            const normalizedH = ((h % 360) + 360) % 360;
            colors.push(this.hslToHex({ h: normalizedH, s: hsl.s, l: hsl.l }));
        }
        return colors;
    }
    generateComplementary(hsl) {
        const complementaryH = (hsl.h + 180) % 360;
        return [
            this.hslToHex(hsl),
            this.hslToHex({ h: complementaryH, s: hsl.s, l: hsl.l }),
            this.hslToHex({ h: hsl.h, s: hsl.s * 0.7, l: hsl.l * 1.2 }),
            this.hslToHex({ h: complementaryH, s: hsl.s * 0.7, l: hsl.l * 1.2 }),
            this.hslToHex({ h: hsl.h, s: hsl.s * 0.5, l: hsl.l * 0.8 })
        ];
    }
    generateTriadic(hsl) {
        const h1 = (hsl.h + 120) % 360;
        const h2 = (hsl.h + 240) % 360;
        return [
            this.hslToHex(hsl),
            this.hslToHex({ h: h1, s: hsl.s, l: hsl.l }),
            this.hslToHex({ h: h2, s: hsl.s, l: hsl.l }),
            this.hslToHex({ h: hsl.h, s: hsl.s * 0.6, l: hsl.l * 1.1 }),
            this.hslToHex({ h: h1, s: hsl.s * 0.6, l: hsl.l * 1.1 })
        ];
    }
    generateTetradic(hsl) {
        const h1 = (hsl.h + 90) % 360;
        const h2 = (hsl.h + 180) % 360;
        const h3 = (hsl.h + 270) % 360;
        return [
            this.hslToHex(hsl),
            this.hslToHex({ h: h1, s: hsl.s, l: hsl.l }),
            this.hslToHex({ h: h2, s: hsl.s, l: hsl.l }),
            this.hslToHex({ h: h3, s: hsl.s, l: hsl.l }),
            this.hslToHex({ h: hsl.h, s: hsl.s * 0.7, l: hsl.l * 0.9 })
        ];
    }
    async showPalettePreview(paletteName) {
        const palette = this.palettes.get(paletteName);
        if (!palette) {
            return;
        }
        const panel = vscode.window.createWebviewPanel('nexaPalettePreview', `Aperçu - ${paletteName}`, vscode.ViewColumn.Two, {
            enableScripts: true,
            localResourceRoots: [this.context.extensionUri]
        });
        panel.webview.html = this.getPalettePreviewHtml(palette);
    }
    async openPaletteEditor(paletteName) {
        const palette = this.palettes.get(paletteName);
        if (!palette) {
            return;
        }
        const panel = vscode.window.createWebviewPanel('nexaPaletteEditor', `Éditer - ${paletteName}`, vscode.ViewColumn.One, {
            enableScripts: true,
            localResourceRoots: [this.context.extensionUri]
        });
        panel.webview.html = this.getPaletteEditorHtml(palette);
        panel.webview.onDidReceiveMessage(async (message) => {
            switch (message.command) {
                case 'updatePalette':
                    palette.colors = message.colors;
                    this.palettes.set(paletteName, palette);
                    await this.savePalettes();
                    vscode.window.showInformationMessage('Palette mise à jour!');
                    break;
                case 'addColor':
                    palette.colors.push(message.color);
                    this.palettes.set(paletteName, palette);
                    await this.savePalettes();
                    break;
                case 'removeColor':
                    palette.colors.splice(message.index, 1);
                    this.palettes.set(paletteName, palette);
                    await this.savePalettes();
                    break;
            }
        }, undefined, this.context.subscriptions);
    }
    async importFromJSON() {
        const fileUri = await vscode.window.showOpenDialog({
            canSelectFiles: true,
            canSelectFolders: false,
            canSelectMany: false,
            filters: {
                'JSON Files': ['json'],
                'All Files': ['*']
            }
        });
        if (!fileUri || fileUri.length === 0) {
            return;
        }
        try {
            const content = fs.readFileSync(fileUri[0].fsPath, 'utf8');
            const paletteData = JSON.parse(content);
            const paletteName = await vscode.window.showInputBox({
                prompt: 'Nom pour la palette importée',
                value: paletteData.name || 'Palette Importée'
            });
            if (paletteName) {
                this.palettes.set(paletteName, paletteData);
                await this.savePalettes();
                vscode.window.showInformationMessage(`Palette "${paletteName}" importée avec succès!`);
            }
        }
        catch (error) {
            vscode.window.showErrorMessage(`Erreur lors de l'import: ${error}`);
        }
    }
    async importFromURL() {
        const url = await vscode.window.showInputBox({
            prompt: 'URL de la palette (Adobe Color, Coolors.co, etc.)',
            placeHolder: 'https://coolors.co/palette/...'
        });
        if (!url) {
            return;
        }
        vscode.window.showInformationMessage('Import depuis URL sera disponible dans une prochaine version');
        // TODO: Implémenter l'import depuis des URLs de palettes
    }
    async exportAsJSON(palette) {
        const saveUri = await vscode.window.showSaveDialog({
            defaultUri: vscode.Uri.file(`${palette.name}.json`),
            filters: {
                'JSON Files': ['json'],
                'All Files': ['*']
            }
        });
        if (saveUri) {
            const content = JSON.stringify(palette, null, 2);
            fs.writeFileSync(saveUri.fsPath, content);
            vscode.window.showInformationMessage(`Palette exportée vers ${saveUri.fsPath}`);
        }
    }
    async exportAsCSS(palette) {
        const saveUri = await vscode.window.showSaveDialog({
            defaultUri: vscode.Uri.file(`${palette.name}.css`),
            filters: {
                'CSS Files': ['css'],
                'All Files': ['*']
            }
        });
        if (saveUri) {
            let content = `:root {\n`;
            palette.colors.forEach((color, index) => {
                const varName = `--color-${palette.name.toLowerCase().replace(/\s+/g, '-')}-${index + 1}`;
                content += `  ${varName}: ${color};\n`;
            });
            content += `}\n`;
            fs.writeFileSync(saveUri.fsPath, content);
            vscode.window.showInformationMessage(`Variables CSS exportées vers ${saveUri.fsPath}`);
        }
    }
    async exportAsSCSS(palette) {
        const saveUri = await vscode.window.showSaveDialog({
            defaultUri: vscode.Uri.file(`${palette.name}.scss`),
            filters: {
                'SCSS Files': ['scss'],
                'All Files': ['*']
            }
        });
        if (saveUri) {
            let content = `// Palette: ${palette.name}\n`;
            palette.colors.forEach((color, index) => {
                const varName = `$color-${palette.name.toLowerCase().replace(/\s+/g, '-')}-${index + 1}`;
                content += `${varName}: ${color};\n`;
            });
            fs.writeFileSync(saveUri.fsPath, content);
            vscode.window.showInformationMessage(`Variables SCSS exportées vers ${saveUri.fsPath}`);
        }
    }
    getPalettePreviewHtml(palette) {
        const colorSwatches = palette.colors.map(color => `<div class="color-swatch" style="background-color: ${color}" title="${color}">
                <span class="color-code">${color}</span>
            </div>`).join('');
        return `
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { 
                        font-family: var(--vscode-font-family); 
                        padding: 20px; 
                        background: var(--vscode-editor-background);
                        color: var(--vscode-editor-foreground);
                    }
                    .palette-header {
                        margin-bottom: 20px;
                        border-bottom: 1px solid var(--vscode-panel-border);
                        padding-bottom: 10px;
                    }
                    .palette-info {
                        margin-bottom: 20px;
                        font-size: 14px;
                        opacity: 0.8;
                    }
                    .color-grid {
                        display: grid;
                        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
                        gap: 15px;
                        margin-bottom: 30px;
                    }
                    .color-swatch {
                        height: 80px;
                        border-radius: 8px;
                        display: flex;
                        align-items: end;
                        justify-content: center;
                        cursor: pointer;
                        transition: transform 0.2s;
                        border: 2px solid transparent;
                    }
                    .color-swatch:hover {
                        transform: scale(1.05);
                        border-color: var(--vscode-focusBorder);
                    }
                    .color-code {
                        background: rgba(0,0,0,0.7);
                        color: white;
                        padding: 4px 8px;
                        border-radius: 4px;
                        font-size: 12px;
                        font-family: monospace;
                        margin-bottom: 8px;
                    }
                    .actions {
                        display: flex;
                        gap: 10px;
                        flex-wrap: wrap;
                    }
                    .btn {
                        padding: 8px 16px;
                        background: var(--vscode-button-background);
                        color: var(--vscode-button-foreground);
                        border: none;
                        border-radius: 4px;
                        cursor: pointer;
                        font-size: 13px;
                    }
                    .btn:hover {
                        background: var(--vscode-button-hoverBackground);
                    }
                </style>
            </head>
            <body>
                <div class="palette-header">
                    <h1>${palette.name}</h1>
                    <div class="palette-info">
                        <div>Type: ${palette.type}</div>
                        <div>Couleurs: ${palette.colors.length}</div>
                        <div>Créée: ${new Date(palette.createdAt).toLocaleDateString()}</div>
                        ${palette.description ? `<div>Description: ${palette.description}</div>` : ''}
                    </div>
                </div>
                
                <div class="color-grid">
                    ${colorSwatches}
                </div>
                
                <div class="actions">
                    <button class="btn" onclick="copyAllColors()">Copier toutes les couleurs</button>
                    <button class="btn" onclick="exportCSS()">Exporter en CSS</button>
                    <button class="btn" onclick="generateVariations()">Générer des variations</button>
                </div>
                
                <script>
                    const colors = ${JSON.stringify(palette.colors)};
                    
                    function copyAllColors() {
                        const colorText = colors.join(', ');
                        navigator.clipboard.writeText(colorText);
                        alert('Couleurs copiées dans le presse-papiers!');
                    }
                    
                    function exportCSS() {
                        let css = ':root {\\n';
                        colors.forEach((color, index) => {
                            css += \`  --color-\${index + 1}: \${color};\\n\`;
                        });
                        css += '}';
                        navigator.clipboard.writeText(css);
                        alert('Variables CSS copiées!');
                    }
                    
                    function generateVariations() {
                        alert('Génération de variations sera disponible prochainement!');
                    }
                    
                    // Copier couleur au clic
                    document.querySelectorAll('.color-swatch').forEach(swatch => {
                        swatch.addEventListener('click', () => {
                            const color = swatch.getAttribute('title');
                            navigator.clipboard.writeText(color);
                            
                            // Feedback visuel
                            const originalBorder = swatch.style.borderColor;
                            swatch.style.borderColor = 'var(--vscode-focusBorder)';
                            setTimeout(() => {
                                swatch.style.borderColor = originalBorder;
                            }, 300);
                        });
                    });
                </script>
            </body>
            </html>
        `;
    }
    getPaletteEditorHtml(palette) {
        return `
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { 
                        font-family: var(--vscode-font-family); 
                        padding: 20px;
                        background: var(--vscode-editor-background);
                        color: var(--vscode-editor-foreground);
                    }
                    .editor-header {
                        margin-bottom: 20px;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    }
                    .color-list {
                        margin-bottom: 20px;
                    }
                    .color-item {
                        display: flex;
                        align-items: center;
                        margin-bottom: 10px;
                        padding: 10px;
                        border: 1px solid var(--vscode-panel-border);
                        border-radius: 4px;
                    }
                    .color-preview {
                        width: 40px;
                        height: 40px;
                        border-radius: 4px;
                        margin-right: 15px;
                        border: 1px solid var(--vscode-panel-border);
                    }
                    .color-input {
                        flex: 1;
                        margin-right: 10px;
                        padding: 5px;
                        background: var(--vscode-input-background);
                        color: var(--vscode-input-foreground);
                        border: 1px solid var(--vscode-input-border);
                        border-radius: 2px;
                    }
                    .btn {
                        padding: 6px 12px;
                        background: var(--vscode-button-background);
                        color: var(--vscode-button-foreground);
                        border: none;
                        border-radius: 2px;
                        cursor: pointer;
                        margin-left: 5px;
                    }
                    .btn:hover {
                        background: var(--vscode-button-hoverBackground);
                    }
                    .btn-danger {
                        background: var(--vscode-errorForeground);
                    }
                    .add-color {
                        display: flex;
                        align-items: center;
                        margin-top: 15px;
                    }
                </style>
            </head>
            <body>
                <div class="editor-header">
                    <h1>Éditer: ${palette.name}</h1>
                    <button class="btn" onclick="savePalette()">Sauvegarder</button>
                </div>
                
                <div class="color-list" id="colorList">
                    <!-- Les couleurs seront ajoutées dynamiquement -->
                </div>
                
                <div class="add-color">
                    <input type="color" id="newColorPicker" value="#3498db">
                    <input type="text" id="newColorInput" placeholder="#3498db" class="color-input" style="width: 100px; margin-left: 10px;">
                    <button class="btn" onclick="addColor()">Ajouter Couleur</button>
                </div>
                
                <script>
                    const vscode = acquireVsCodeApi();
                    let colors = ${JSON.stringify(palette.colors)};
                    
                    function renderColors() {
                        const colorList = document.getElementById('colorList');
                        colorList.innerHTML = '';
                        
                        colors.forEach((color, index) => {
                            const colorItem = document.createElement('div');
                            colorItem.className = 'color-item';
                            colorItem.innerHTML = \`
                                <div class="color-preview" style="background-color: \${color}"></div>
                                <input type="text" class="color-input" value="\${color}" onchange="updateColor(\${index}, this.value)">
                                <button class="btn btn-danger" onclick="removeColor(\${index})">Supprimer</button>
                            \`;
                            colorList.appendChild(colorItem);
                        });
                    }
                    
                    function updateColor(index, newColor) {
                        if (/^#[0-9A-Fa-f]{6}$/.test(newColor)) {
                            colors[index] = newColor;
                            renderColors();
                        }
                    }
                    
                    function removeColor(index) {
                        colors.splice(index, 1);
                        renderColors();
                        vscode.postMessage({
                            command: 'removeColor',
                            index: index
                        });
                    }
                    
                    function addColor() {
                        const colorPicker = document.getElementById('newColorPicker');
                        const colorInput = document.getElementById('newColorInput');
                        const newColor = colorInput.value || colorPicker.value;
                        
                        if (/^#[0-9A-Fa-f]{6}$/.test(newColor)) {
                            colors.push(newColor);
                            renderColors();
                            colorInput.value = '';
                            
                            vscode.postMessage({
                                command: 'addColor',
                                color: newColor
                            });
                        } else {
                            alert('Format de couleur invalide. Utilisez #RRGGBB');
                        }
                    }
                    
                    function savePalette() {
                        vscode.postMessage({
                            command: 'updatePalette',
                            colors: colors
                        });
                    }
                    
                    // Synchroniser le color picker avec l'input text
                    document.getElementById('newColorPicker').addEventListener('change', function() {
                        document.getElementById('newColorInput').value = this.value;
                    });
                    
                    document.getElementById('newColorInput').addEventListener('input', function() {
                        if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
                            document.getElementById('newColorPicker').value = this.value;
                        }
                    });
                    
                    // Rendu initial
                    renderColors();
                </script>
            </body>
            </html>
        `;
    }
    async loadPalettes() {
        const palettesPath = this.getPalettesFilePath();
        if (fs.existsSync(palettesPath)) {
            try {
                const content = fs.readFileSync(palettesPath, 'utf8');
                const palettesData = JSON.parse(content);
                this.palettes.clear();
                for (const [name, palette] of Object.entries(palettesData)) {
                    this.palettes.set(name, palette);
                }
            }
            catch (error) {
                console.error('Erreur lors du chargement des palettes:', error);
            }
        }
    }
    async savePalettes() {
        const palettesPath = this.getPalettesFilePath();
        const palettesDir = path.dirname(palettesPath);
        if (!fs.existsSync(palettesDir)) {
            fs.mkdirSync(palettesDir, { recursive: true });
        }
        const palettesData = Object.fromEntries(this.palettes);
        fs.writeFileSync(palettesPath, JSON.stringify(palettesData, null, 2));
    }
    getPalettesFilePath() {
        return path.join(this.context.globalStorageUri.fsPath, 'palettes.json');
    }
    // Utilitaires de conversion de couleurs
    hexToHsl(hex) {
        const r = parseInt(hex.slice(1, 3), 16) / 255;
        const g = parseInt(hex.slice(3, 5), 16) / 255;
        const b = parseInt(hex.slice(5, 7), 16) / 255;
        const max = Math.max(r, g, b);
        const min = Math.min(r, g, b);
        let h = 0;
        let s = 0;
        const l = (max + min) / 2;
        if (max !== min) {
            const d = max - min;
            s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
            switch (max) {
                case r:
                    h = (g - b) / d + (g < b ? 6 : 0);
                    break;
                case g:
                    h = (b - r) / d + 2;
                    break;
                case b:
                    h = (r - g) / d + 4;
                    break;
            }
            h /= 6;
        }
        return { h: h * 360, s, l };
    }
    hslToHex(hsl) {
        const h = hsl.h / 360;
        const s = hsl.s;
        const l = hsl.l;
        const hue2rgb = (p, q, t) => {
            if (t < 0)
                t += 1;
            if (t > 1)
                t -= 1;
            if (t < 1 / 6)
                return p + (q - p) * 6 * t;
            if (t < 1 / 2)
                return q;
            if (t < 2 / 3)
                return p + (q - p) * (2 / 3 - t) * 6;
            return p;
        };
        let r, g, b;
        if (s === 0) {
            r = g = b = l; // achromatic
        }
        else {
            const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
            const p = 2 * l - q;
            r = hue2rgb(p, q, h + 1 / 3);
            g = hue2rgb(p, q, h);
            b = hue2rgb(p, q, h - 1 / 3);
        }
        const toHex = (c) => {
            const hex = Math.round(c * 255).toString(16);
            return hex.length === 1 ? '0' + hex : hex;
        };
        return `#${toHex(r)}${toHex(g)}${toHex(b)}`;
    }
}
exports.ColorPalette = ColorPalette;
//# sourceMappingURL=colorPalette.js.map