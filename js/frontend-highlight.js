// assets/frontend-highlight.js
document.addEventListener('DOMContentLoaded', function () {
  const params = new URLSearchParams(window.location.search);
  const keyword = params.get('highlight');
  if (!keyword) return;

  const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, {
    acceptNode: function (node) {
      return node.nodeValue.toLowerCase().includes(keyword.toLowerCase()) ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
    }
  });

  const nodes = [];
  while (walker.nextNode()) {
    nodes.push(walker.currentNode);
  }

  nodes.forEach(textNode => {
    const parent = textNode.parentNode;
    const regex = new RegExp(`(${keyword})`, 'gi');
    const newHTML = textNode.nodeValue.replace(regex, '<mark>$1</mark>');
    const span = document.createElement('span');
    span.innerHTML = newHTML;
    parent.replaceChild(span, textNode);
  });
});
