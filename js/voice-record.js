document.addEventListener('DOMContentLoaded', () => {
  let mediaRecorder, audioChunks = [];
  const recordBtn      = document.getElementById('record_btn');
  const finishBtn      = document.getElementById('finish_btn');
  const abortBtn       = document.getElementById('abort_btn');
  const status         = document.getElementById('recording_status');
  const preview        = document.getElementById('audio_preview');
  const voiceDataInput = document.getElementById('voice_data');

  function resetRecording() {
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
      mediaRecorder.stop();
    }
    mediaRecorder = null;
    audioChunks = [];
    voiceDataInput.value = '';
    preview.src = '';
    preview.style.display = 'none';
    status.textContent = '';
    recordBtn.disabled = false;
    finishBtn.disabled = true;
    abortBtn.disabled = true;
  }

  recordBtn.addEventListener('click', async () => {
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
      mediaRecorder = new MediaRecorder(stream);
      audioChunks = [];

      mediaRecorder.ondataavailable = e => audioChunks.push(e.data);
      mediaRecorder.onstop = () => {
        const blob = new Blob(audioChunks, { type: 'audio/webm' });
        const reader = new FileReader();

        reader.onloadend = () => {
          voiceDataInput.value = reader.result;
        };

        reader.readAsDataURL(blob);
        preview.src = URL.createObjectURL(blob);
        preview.style.display = 'block';
      };

      mediaRecorder.start();
      status.textContent = "Recording…";
      recordBtn.disabled = true;
      finishBtn.disabled = false;
      abortBtn.disabled = false;

      setTimeout(() => {
        if (mediaRecorder && mediaRecorder.state === 'recording') {
          mediaRecorder.stop();
          status.textContent = "Recording complete (1 min max)";
          finishBtn.disabled = true;
        }
      }, 60000);
    } catch (err) {
      alert('Microphone access denied or not available.');
    }
  });

  finishBtn.addEventListener('click', () => {
    if (mediaRecorder && mediaRecorder.state === 'recording') {
      mediaRecorder.stop();
      status.textContent = "Recording finished.";
      finishBtn.disabled = true;
    }
  });

  abortBtn.addEventListener('click', () => {
    resetRecording();
  });
});
