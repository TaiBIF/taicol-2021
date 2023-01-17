export default (container, title) => {
    let filename = 'test';

    var preHtml = "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>" +
        "<head><style>.is-orange { color: #F78500 } " +
        ".is-title * { font-weight: bold } .is-indent {margin-left: 1cm; text-indent: 1cm;  } " +
        ".has-text-success{ color: #48c774 } .has-text-danger{ color: red } .title{ text-align: center; font-weight: bold; font-size: 18px; }</style><meta charset='utf-8'>" +
        "<title>" + title + "</title></head><body><p class='title'>" + title + "</p>";
    var postHtml = "</body></html>";

    const target = container.cloneNode(true);
    target.querySelectorAll('.buttons').forEach(function (item, index) {
        item.parentNode.removeChild(item);
    });
    target.querySelectorAll('.is-indent > .usage-content > p').forEach(function (item, index) {
        item.prepend('　');
    });

    var html = preHtml + target.innerHTML.replace(/div/g, 'span').replace(/&nbsp;/, ' ') + postHtml;

    var blob = new Blob(['\ufeff', html], {
        type: 'application/msword'
    });

    // Specify link url
    var url = 'data:application/vnd.openxmlformats-officedocument.wordprocessingml.document;charset=utf-8,' + encodeURIComponent(html);

    filename = filename ? filename + '.doc' : 'document.doc';

    var downloadLink = document.createElement("a");

    document.body.appendChild(downloadLink);

    const globalNavigator: any = window.navigator;
    if (globalNavigator.msSaveOrOpenBlob) {
        globalNavigator.msSaveOrOpenBlob(blob, filename);
    } else {
        downloadLink.href = url;

        downloadLink.download = filename;

        downloadLink.click();
    }
    document.body.removeChild(downloadLink);

}
