/*
 * The MIT License (MIT)
 * 
 * Copyright (c) 2019 Gregory Petrosyan
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, 
 * and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 * 
 * flatpickr v4.6.4,, @license MIT 
 */

(function (global, factory) {
  typeof exports === 'object' && typeof module !== 'undefined' ? factory(exports) :
  typeof define === 'function' && define.amd ? define(['exports'], factory) :
  (global = global || self, factory(global.cy = {}));
}(this, (function (exports) { 'use strict';

  var fp = typeof window !== "undefined" && window.flatpickr !== undefined
      ? window.flatpickr
      : {
          l10ns: {},
      };
  var Welsh = {
      weekdays: {
          shorthand: ["Sul", "Llun", "Maw", "Mer", "Iau", "Gwe", "Sad"],
          longhand: [
              "Dydd Sul",
              "Dydd Llun",
              "Dydd Mawrth",
              "Dydd Mercher",
              "Dydd Iau",
              "Dydd Gwener",
              "Dydd Sadwrn",
          ],
      },
      months: {
          shorthand: [
              "Ion",
              "Chwef",
              "Maw",
              "Ebr",
              "Mai",
              "Meh",
              "Gorff",
              "Awst",
              "Medi",
              "Hyd",
              "Tach",
              "Rhag",
          ],
          longhand: [
              "Ionawr",
              "Chwefror",
              "Mawrth",
              "Ebrill",
              "Mai",
              "Mehefin",
              "Gorffennaf",
              "Awst",
              "Medi",
              "Hydref",
              "Tachwedd",
              "Rhagfyr",
          ],
      },
      firstDayOfWeek: 1,
      ordinal: function (nth) {
          if (nth === 1)
              return "af";
          if (nth === 2)
              return "ail";
          if (nth === 3 || nth === 4)
              return "ydd";
          if (nth === 5 || nth === 6)
              return "ed";
          if ((nth >= 7 && nth <= 10) ||
              nth == 12 ||
              nth == 15 ||
              nth == 18 ||
              nth == 20)
              return "fed";
          if (nth == 11 ||
              nth == 13 ||
              nth == 14 ||
              nth == 16 ||
              nth == 17 ||
              nth == 19)
              return "eg";
          if (nth >= 21 && nth <= 39)
              return "ain";
          // Inconclusive.
          return "";
      },
      time_24hr: true,
  };
  fp.l10ns.cy = Welsh;
  var cy = fp.l10ns;

  exports.Welsh = Welsh;
  exports.default = cy;

  Object.defineProperty(exports, '__esModule', { value: true });

})));
