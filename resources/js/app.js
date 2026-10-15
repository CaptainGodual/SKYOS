import * as coreui from '@coreui/coreui'

window.coreui = coreui

document.querySelectorAll('[data-skyos-sidebar-toggle]').forEach((button) => {
  button.addEventListener('click', () => {
    document.body.classList.toggle('sidebar-visible')
  })
})
