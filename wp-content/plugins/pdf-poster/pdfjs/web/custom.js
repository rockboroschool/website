document.addEventListener("DOMContentLoaded", function () {
  function parseURLParams(url) {
    var queryStart = url.indexOf("?") + 1,
      queryEnd = url.indexOf("#") + 1 || url.length + 1,
      query = url.slice(queryStart, queryEnd - 1),
      pairs = query.replace(/\+/g, " ").split("&"),
      parms = {},
      i,
      n,
      v,
      nv;

    if (query === url || query === "") return;

    for (i = 0; i < pairs.length; i++) {
      nv = pairs[i].split("=", 2);
      n = decodeURIComponent(nv[0]);
      v = decodeURIComponent(nv[1]);

      if (!parms.hasOwnProperty(n)) parms[n] = [];
      parms[n] = nv.length === 2 ? v : null;
    }
    return parms;
  }

  const parseURL = parseURLParams(location.href);
  const openFile = document.getElementById("openFile");
  const sidebarToggle = document.getElementById("sidebarToggle");
  const print = document.getElementById("print");
  const download = document.getElementById("download");
  const secondaryOpenFile = document.getElementById("secondaryOpenFile");
  const secondaryPrint = document.getElementById("secondaryPrint");
  const secondaryDownload = document.getElementById("secondaryDownload");

  if (openFile && parseURL?.open) {
    openFile.style.display = "none";
  }

  if (print && parseURL?.print != "true") {
    print.style.display = "none";
  }

  if (download && parseURL?.download != "true") {
    download.style.display = "none";
  }

  if (secondaryOpenFile && parseURL?.open) {
    secondaryOpenFile.style.display = "none";
  }

  if (secondaryPrint && parseURL?.print != "true") {
    secondaryPrint.style.display = "none";
  }

  if (secondaryDownload && parseURL?.download != "true") {
    secondaryDownload.style.display = "none";
  }

  //sidebar toggle
  if (sidebarToggle && parseURL?.side != "true") {
    sidebarToggle.style.display = "none";
  }
});
