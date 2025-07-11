document.addEventListener('DOMContentLoaded', function () {
  const ambianceAudio = document.getElementById('ambianceAudio');
  const soundToggle = document.getElementById('soundToggle');
  const volumeRange = document.getElementById('volumeRange');
  const volumeIcon = document.getElementById('volumeIcon');
  if (ambianceAudio && soundToggle && volumeRange && volumeIcon) {
    function updateAmbianceVolume() {
      const vol = Math.max(0, Math.min(1, volumeRange.value / 100));
      ambianceAudio.volume = vol;
      if (volumeRange.value == 0 || !soundToggle.checked) {
        volumeIcon.className = 'ri-volume-mute-line text-primary';
      } else if (volumeRange.value < 50) {
        volumeIcon.className = 'ri-volume-down-line text-primary';
      } else {
        volumeIcon.className = 'ri-volume-up-line text-primary';
      }
    }
    function updateAmbiancePlayback() {
      if (soundToggle.checked) {
        ambianceAudio.muted = false;
        if (ambianceAudio.paused) {
          ambianceAudio.play().catch(()=>{});
        }
      } else {
        ambianceAudio.muted = true;
        ambianceAudio.pause();
      }
      updateAmbianceVolume();
    }
    soundToggle.checked = true;
    updateAmbiancePlayback();
    soundToggle.addEventListener('change', updateAmbiancePlayback);
    volumeRange.addEventListener('input', function() {
      updateAmbianceVolume();
      if (soundToggle.checked && ambianceAudio.paused) {
        ambianceAudio.play().catch(()=>{});
      }
    });
    let ambianceStarted = false;
    function tryStartAmbiance() {
      if (!ambianceStarted && soundToggle.checked) {
        ambianceAudio.play().catch(()=>{});
        ambianceStarted = true;
      }
    }
    ambianceAudio.play().catch(()=>{
      document.addEventListener('click', tryStartAmbiance, { once: true });
      document.addEventListener('keydown', tryStartAmbiance, { once: true });
    });
    updateAmbianceVolume();
  }
}); 
