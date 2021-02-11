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
  (global = global || self, factory(global.lt = {}));
}(this, (function (exports) { 'use strict';

  var fp = typeof window !== "undefined" && window.flatpickr !== undefined
      ? window.flatpickr
      : {
          l10ns: {},
      };
  var Lithuanian = {
      weekdays: {
          shorthand: ["S", "Pr", "A", "T", "K", "Pn", "Š"],
          longhand: [
              "Sekmadienis",
              "Pirmadienis",
              "Antradienis",
              "Trečiadienis",
              "Ketvirtadienis",
              "Penktadienis",
              "Šeštadienis",
          ],
      },
      months: {
          shorthand: [
              "Sau",
              "Vas",
              "Kov",
              "Bal",
              "Geg",
              "Bir",
              "Lie",
              "Rgp",
              "Rgs",
              "Spl",
              "Lap",
              "Grd",
          ],
          longhand: [
              "Sausis",
              "Vasaris",
              "Kovas",
              "Balandis",
              "Gegužė",
              "Birželis",
              "Liepa",
              "Rugpjūtis",
              "Rugsėjis",
              "Spalis",
              "Lapkritis",
              "Gruodis",
          ],
      },
      firstDayOfWeek: 1,
      ordinal: function () {
          return "-a";
      },
      weekAbbreviation: "Sav",
      scrollTitle: "Keisti laiką pelės rateliu",
      toggleTitle: "Perjungti laiko formatą",
      time_24hr: true,
  };
  fp.l10ns.lt = Lithuanian;
  var lt = fp.l10ns;

  exports.Lithuanian = Lithuanian;
  exports.default = lt;

  Object.defineProperty(exports, '__esModule', { value: true });

})));
