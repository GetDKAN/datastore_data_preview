(function (Drupal, once) {
  function ajaxNavigate(wrapper, url) {
    var pageSizeSelect = wrapper.querySelector('.data-preview-page-size-select');
    var pageSizeValue = pageSizeSelect ? pageSizeSelect.value : null;

    window.history.pushState({}, '', url.toString());
    wrapper.classList.add('data-preview-loading');

    fetch(url.toString(), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (response) { return response.text(); })
      .then(function (html) {
        var doc = new DOMParser().parseFromString(html, 'text/html');
        var newWrapper = doc.querySelector('.data-preview-wrapper');
        if (newWrapper) {
          Drupal.detachBehaviors(wrapper);
          wrapper.innerHTML = newWrapper.innerHTML;
          if (pageSizeValue) {
            var newSelect = wrapper.querySelector('.data-preview-page-size-select');
            if (newSelect) {
              newSelect.value = pageSizeValue;
            }
          }
          wrapper.classList.remove('data-preview-loading');
          Drupal.attachBehaviors(wrapper);
        }
      })
      .catch(function () {
        wrapper.classList.remove('data-preview-loading');
        window.location.href = url.toString();
      });
  }

  Drupal.behaviors.dataPreviewAjax = {
    attach(context) {
      once('data-preview-ajax', '.data-preview-wrapper', context)
        .forEach(function (wrapper) {
          // Delegated change: page-size select.
          wrapper.addEventListener('change', function (e) {
            if (!e.target.matches('.data-preview-page-size-select')) return;
            var select = e.target;
            var url = new URL(window.location);
            url.searchParams.set(select.dataset.paramName, select.value);
            url.searchParams.delete('page');
            ajaxNavigate(wrapper, url);
          });

          // Delegated click: sort links + pager links.
          wrapper.addEventListener('click', function (e) {
            var sortLink = e.target.closest('.data-preview-table thead th a');
            if (sortLink) {
              e.preventDefault();
              e.stopPropagation();
              ajaxNavigate(wrapper, new URL(sortLink.href));
              return;
            }
            var pagerLink = e.target.closest('.data-preview-footer a');
            if (pagerLink) {
              e.preventDefault();
              e.stopPropagation();
              ajaxNavigate(wrapper, new URL(pagerLink.href));
            }
          });
        });
    }
  };
})(Drupal, once);
