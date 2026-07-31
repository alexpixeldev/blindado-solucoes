// Conversor universal de áudio para MP3 (client-side).
// - WAV GSM 6.10 (WAVE_FORMAT_GSM610): decodificado via GSMDecoder
// - Demais formatos: decodificados via Web Audio (decodeAudioData)
// - Saída sempre MP3 (lamejs)
(function (global) {
    'use strict';

    function ascii(dv, offset, len) {
        let s = '';
        for (let i = 0; i < len; i++) s += String.fromCharCode(dv.getUint8(offset + i));
        return s;
    }

    function isGsmWav(arrayBuffer) {
        const dv = new DataView(arrayBuffer);
        if (dv.byteLength < 44) return false;
        if (ascii(dv, 0, 4) !== 'RIFF' || ascii(dv, 8, 4) !== 'WAVE') return false;
        let off = 12;
        while (off + 8 <= dv.byteLength) {
            const id = ascii(dv, off, 4);
            const size = dv.getUint32(off + 4, true);
            if (id === 'fmt ') return dv.getUint16(off + 8, true) === 49;
            off += 8 + size + (size % 2);
        }
        return false;
    }

    function decodeGsmWavToPcm(arrayBuffer) {
        const dv = new DataView(arrayBuffer);
        let off = 12, blockAlign = 0, dataStart = -1, dataSize = 0, sampleRate = 0;
        while (off + 8 <= dv.byteLength) {
            const id = ascii(dv, off, 4);
            const size = dv.getUint32(off + 4, true);
            if (id === 'fmt ') {
                blockAlign = dv.getUint16(off + 20, true);
                sampleRate = dv.getUint32(off + 12, true);
            }
            if (id === 'data') { dataStart = off + 8; dataSize = size; break; }
            off += 8 + size + (size % 2);
        }
        if (blockAlign <= 0 || dataStart < 0) throw new Error('WAV inválido.');
        const blocks = Math.floor(dataSize / blockAlign);
        const src = new Uint8Array(arrayBuffer, dataStart, dataSize);
        const blockBuf = new Uint8Array(65);
        const dec = new global.GSMDecoder();
        dec.decoderInit();
        let currentFrame = 0;
        // MS GSM: 65 bytes = 520 bits = 2 frames de 260 bits, bits LSB-first, primeiro bit = LSB do parâmetro
        dec.UnpackBitStream = function (ignored, idx, P) {
            let pos = currentFrame * 260;
            function gb(n) {
                let v = 0;
                for (let i = 0; i < n; i++) v |= ((blockBuf[(pos + i) >> 3] >> ((pos + i) & 7)) & 1) << i;
                pos += n;
                return v;
            }
            P[0] = gb(6); P[1] = gb(6); P[2] = gb(5); P[3] = gb(5);
            P[4] = gb(4); P[5] = gb(4); P[6] = gb(3); P[7] = gb(3);
            for (let n = 0; n < 4; n++) {
                const base = 8 + n * 17;
                P[base] = gb(7); P[base + 1] = gb(2); P[base + 2] = gb(2); P[base + 3] = gb(6);
                for (let j = 0; j < 13; j++) P[base + 4 + j] = gb(3);
            }
            return true;
        };
        const pcm = new Int16Array(blocks * 320);
        const outBytes = new Uint8Array(320);
        let idx = 0;
        for (let b = 0; b < blocks; b++) {
            for (let i = 0; i < 65; i++) blockBuf[i] = src[b * 65 + i];
            for (let f = 0; f < 2; f++) {
                currentFrame = f;
                dec.decodeFrame(blockBuf, 0, outBytes, 0);
                for (let i = 0; i < 160; i++) {
                    let s = (outBytes[i * 2 + 1] << 8) | outBytes[i * 2];
                    if (s >= 32768) s -= 65536;
                    pcm[idx++] = s;
                }
            }
        }
        return { pcm: pcm, sampleRate: sampleRate || 8000, channels: 1 };
    }

    function bufferToInt16Mono(audioBuffer) {
        const ch = audioBuffer.numberOfChannels;
        const len = audioBuffer.length;
        const out = new Int16Array(len);
        const data = [];
        for (let c = 0; c < ch; c++) data.push(audioBuffer.getChannelData(c));
        for (let i = 0; i < len; i++) {
            let s = 0;
            for (let c = 0; c < ch; c++) s += data[c][i];
            s /= ch;
            s = Math.max(-1, Math.min(1, s));
            out[i] = s < 0 ? s * 0x8000 : s * 0x7FFF;
        }
        return out;
    }

    function encodeMp3(pcm, sampleRate, onprogress) {
        const kbps = sampleRate <= 12000 ? 32 : 64;
        const encoder = new global.lamejs.Mp3Encoder(1, sampleRate, kbps);
        const mp3Data = [];
        const CHUNK = 1152;
        for (let i = 0; i < pcm.length; i += CHUNK) {
            const end = Math.min(i + CHUNK, pcm.length);
            const buf = encoder.encodeBuffer(pcm.subarray(i, end));
            if (buf.length > 0) mp3Data.push(new Int8Array(buf));
            if (onprogress) onprogress(Math.min(1, (i + CHUNK) / pcm.length));
        }
        const flush = encoder.flush();
        if (flush.length > 0) mp3Data.push(new Int8Array(flush));
        return new Blob(mp3Data, { type: 'audio/mpeg' });
    }

    function convertAudioToMp3(file, onDone, onError, onprogress) {
        if ((file.name.split('.').pop() || '').toLowerCase() === 'mp3') {
            onDone(file);
            return;
        }
        file.arrayBuffer().then(function (arrayBuffer) {
            let decoded;
            try {
                if (isGsmWav(arrayBuffer)) {
                    decoded = decodeGsmWavToPcm(arrayBuffer);
                } else {
                    const ctx = new (global.AudioContext || global.webkitAudioContext)();
                    ctx.decodeAudioData(arrayBuffer, function (audioBuffer) {
                        try {
                            const blob = encodeMp3(bufferToInt16Mono(audioBuffer), audioBuffer.sampleRate, onprogress);
                            const name = file.name.replace(/\.[^.]+$/, '') + '.mp3';
                            onDone(new File([blob], name, { type: 'audio/mpeg' }));
                        } catch (e) { onError(e); }
                        ctx.close();
                    }, function () {
                        ctx.close();
                        onError(new Error('Formato de áudio não suportado pelo navegador.'));
                    });
                    return;
                }
            } catch (e) {
                onError(e);
                return;
            }
            try {
                const blob = encodeMp3(decoded.pcm, decoded.sampleRate, onprogress);
                const name = file.name.replace(/\.[^.]+$/, '') + '.mp3';
                onDone(new File([blob], name, { type: 'audio/mpeg' }));
            } catch (e) {
                onError(e);
            }
        }).catch(onError);
    }

    global.BlindadoAudio = { convertAudioToMp3: convertAudioToMp3 };
})(typeof window !== 'undefined' ? window : globalThis);
