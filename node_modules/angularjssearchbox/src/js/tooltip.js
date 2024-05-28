'use strict';
angular.module('angularjssearchbox.tooltip', ['mgcrea.ngStrap.helpers.dimensions']).provider('$sbtooltip', function () {
  var defaults = this.defaults = {
      animation: 'am-fade',
      prefixClass: 'tooltip',
      prefixEvent: 'tooltip',
      container: false,
      placement: 'top',
      template: 'templates/tooltip.html',
      contentTemplate: false,
      trigger: 'hover focus',
      keyboard: false,
      html: false,
      show: false,
      title: '',
      type: '',
      delay: 0
    };
  this.$get = [
    '$window',
    '$rootScope',
    '$compile',
    '$q',
    '$templateCache',
    '$http',
    '$animate',
    '$timeout',
    'dimensions',
    '$$rAF',
    function ($window, $rootScope, $compile, $q, $templateCache, $http, $animate, $timeout, dimensions, $$rAF) {
      var trim = String.prototype.trim;
      var isTouch = 'createTouch' in $window.document;
      var htmlReplaceRegExp = /ng-bind="/gi;
      function TooltipFactory(element, config) {
        var $sbtooltip = {};
        // Common vars
        var nodeName = element[0].nodeName.toLowerCase();
        var options = $sbtooltip.$options = angular.extend({}, defaults, config);
        $sbtooltip.$promise = fetchTemplate(options.template);
        var scope = $sbtooltip.$scope = options.scope && options.scope.$new() || $rootScope.$new();
        if (options.delay && angular.isString(options.delay)) {
          options.delay = parseFloat(options.delay);
        }
        // Support scope as string options
        if (options.title) {
          $sbtooltip.$scope.title = options.title;
        }
        // Provide scope helpers
        scope.$hide = function () {
          scope.$$postDigest(function () {
            $sbtooltip.hide();
          });
        };
        scope.$show = function () {
          scope.$$postDigest(function () {
            $sbtooltip.show();
          });
        };
        scope.$toggle = function () {
          scope.$$postDigest(function () {
            $sbtooltip.toggle();
          });
        };
        $sbtooltip.$isShown = scope.$isShown = false;
        // Private vars
        var timeout, hoverState;
        // Support contentTemplate option
        if (options.contentTemplate) {
          $sbtooltip.$promise = $sbtooltip.$promise.then(function (template) {
            var templateEl = angular.element(template);
            return fetchTemplate(options.contentTemplate).then(function (contentTemplate) {
              var contentEl = findElement('[ng-bind="content"]', templateEl[0]);
              if (!contentEl.length)
                contentEl = findElement('[ng-bind="title"]', templateEl[0]);
              contentEl.removeAttr('ng-bind').html(contentTemplate);
              return templateEl[0].outerHTML;
            });
          });
        }
        // Fetch, compile then initialize tooltip
        var tipLinker, tipElement, tipTemplate, tipContainer;
        $sbtooltip.$promise.then(function (template) {
          if (angular.isObject(template))
            template = template.data;
          if (options.html)
            template = template.replace(htmlReplaceRegExp, 'ng-bind-html="');
          template = trim.apply(template);
          tipTemplate = template;
          tipLinker = $compile(template);
          $sbtooltip.init();
        });
        $sbtooltip.init = function () {
          // Options: delay
          if (options.delay && angular.isNumber(options.delay)) {
            options.delay = {
              show: options.delay,
              hide: options.delay
            };
          }
          // Replace trigger on touch devices ?
          // if(isTouch && options.trigger === defaults.trigger) {
          //   options.trigger.replace(/hover/g, 'click');
          // }
          // Options : container
          if (options.container === 'self') {
            tipContainer = element;
          } else if (options.container) {
            tipContainer = findElement(options.container);
          }
          // Options: trigger
          var triggers = options.trigger.split(' ');
          angular.forEach(triggers, function (trigger) {
            if (trigger === 'click') {
              element.on('click', $sbtooltip.toggle);
            } else if (trigger !== 'manual') {
              element.on(trigger === 'hover' ? 'mouseenter' : 'focus', $sbtooltip.enter);
              element.on(trigger === 'hover' ? 'mouseleave' : 'blur', $sbtooltip.leave);
              nodeName === 'button' && trigger !== 'hover' && element.on(isTouch ? 'touchstart' : 'mousedown', $sbtooltip.$onFocusElementMouseDown);
            }
          });
          // Options: show
          if (options.show) {
            scope.$$postDigest(function () {
              options.trigger === 'focus' ? element[0].focus() : $sbtooltip.show();
            });
          }
        };
        $sbtooltip.destroy = function () {
          // Unbind events
          var triggers = options.trigger.split(' ');
          for (var i = triggers.length; i--;) {
            var trigger = triggers[i];
            if (trigger === 'click') {
              element.off('click', $sbtooltip.toggle);
            } else if (trigger !== 'manual') {
              element.off(trigger === 'hover' ? 'mouseenter' : 'focus', $sbtooltip.enter);
              element.off(trigger === 'hover' ? 'mouseleave' : 'blur', $sbtooltip.leave);
              nodeName === 'button' && trigger !== 'hover' && element.off(isTouch ? 'touchstart' : 'mousedown', $sbtooltip.$onFocusElementMouseDown);
            }
          }
          // Remove element
          if (tipElement) {
            tipElement.remove();
            tipElement = null;
          }
          // Destroy scope
          scope.$destroy();
        };
        $sbtooltip.enter = function () {
          clearTimeout(timeout);
          hoverState = 'in';
          if (!options.delay || !options.delay.show) {
            return $sbtooltip.show();
          }
          timeout = setTimeout(function () {
            if (hoverState === 'in')
              $sbtooltip.show();
          }, options.delay.show);
        };
        $sbtooltip.show = function () {
          scope.$emit(options.prefixEvent + '.show.before', $sbtooltip);
          var parent = options.container ? tipContainer : null;
          var after = options.container ? null : element;
          // Hide any existing tipElement
          if (tipElement)
            tipElement.remove();
          // Fetch a cloned element linked from template
          tipElement = $sbtooltip.$element = tipLinker(scope, function (clonedElement, scope) {
          });
          // Set the initial positioning.
          tipElement.css({
            top: '0px',
            left: '0px',
            display: 'block'
          }).addClass(options.placement);
          // Options: animation
          if (options.animation)
            tipElement.addClass(options.animation);
          // Options: type
          if (options.type)
            tipElement.addClass(options.prefixClass + '-' + options.type);
          $animate.enter(tipElement, parent, after, function () {
            scope.$emit(options.prefixEvent + '.show', $sbtooltip);
          });
          $sbtooltip.$isShown = scope.$isShown = true;
          scope.$$phase || scope.$root.$$phase || scope.$digest();
          $$rAF($sbtooltip.$applyPlacement);
          // var a = bodyEl.offsetWidth + 1; ?
          // Bind events
          if (options.keyboard) {
            if (options.trigger !== 'focus') {
              $sbtooltip.focus();
              tipElement.on('keyup', $sbtooltip.$onKeyUp);
            } else {
              element.on('keyup', $sbtooltip.$onFocusKeyUp);
            }
          }
        };
        $sbtooltip.leave = function () {
          clearTimeout(timeout);
          hoverState = 'out';
          if (!options.delay || !options.delay.hide) {
            return $sbtooltip.hide();
          }
          timeout = setTimeout(function () {
            if (hoverState === 'out') {
              $sbtooltip.hide();
            }
          }, options.delay.hide);
        };
        $sbtooltip.hide = function (blur) {
          if (!$sbtooltip.$isShown)
            return;
          scope.$emit(options.prefixEvent + '.hide.before', $sbtooltip);
          $animate.leave(tipElement, function () {
            scope.$emit(options.prefixEvent + '.hide', $sbtooltip);
          });
          $sbtooltip.$isShown = scope.$isShown = false;
          scope.$$phase || scope.$root.$$phase || scope.$digest();
          // Unbind events
          if (options.keyboard && tipElement !== null) {
            tipElement.off('keyup', $sbtooltip.$onKeyUp);
          }
          // Allow to blur the input when hidden, like when pressing enter key
          if (blur && options.trigger === 'focus') {
            return element[0].blur();
          }
        };
        $sbtooltip.toggle = function () {
          $sbtooltip.$isShown ? $sbtooltip.leave() : $sbtooltip.enter();
        };
        $sbtooltip.focus = function () {
          tipElement[0].focus();
        };
        // Protected methods
        $sbtooltip.$applyPlacement = function () {
          if (!tipElement)
            return;
          // Get the position of the tooltip element.
          var elementPosition = getPosition();
          // Get the height and width of the tooltip so we can center it.
          var tipWidth = tipElement.prop('offsetWidth'), tipHeight = tipElement.prop('offsetHeight');
          // Get the tooltip's top and left coordinates to center it with this directive.
          var tipPosition = getCalculatedOffset(options.placement, elementPosition, tipWidth, tipHeight);
          // Now set the calculated positioning.
          tipPosition.top += 'px';
          tipPosition.left += 'px';
          tipElement.css(tipPosition);
        };
        $sbtooltip.$onKeyUp = function (evt) {
          evt.which === 27 && $sbtooltip.hide();
        };
        $sbtooltip.$onFocusKeyUp = function (evt) {
          evt.which === 27 && element[0].blur();
        };
        $sbtooltip.$onFocusElementMouseDown = function (evt) {
          evt.preventDefault();
          evt.stopPropagation();
          // Some browsers do not auto-focus buttons (eg. Safari)
          $sbtooltip.$isShown ? element[0].blur() : element[0].focus();
        };
        // Private methods
        function getPosition() {
          if (options.container === 'body') {
            return dimensions.offset(element[0]);
          } else {
            return dimensions.position(element[0]);
          }
        }
        function getCalculatedOffset(placement, position, actualWidth, actualHeight) {
          var offset;
          var split = placement.split('-');
          switch (split[0]) {
          case 'right':
            offset = {
              top: position.top + position.height / 2 - actualHeight / 2,
              left: position.left + position.width
            };
            break;
          case 'bottom':
            offset = {
              top: position.top + position.height,
              left: position.left + position.width / 2 - actualWidth / 2
            };
            break;
          case 'left':
            offset = {
              top: position.top + position.height / 2 - actualHeight / 2,
              left: position.left - actualWidth
            };
            break;
          default:
            offset = {
              top: position.top - actualHeight,
              left: position.left + position.width / 2 - actualWidth / 2
            };
            break;
          }
          if (!split[1]) {
            return offset;
          }
          // Add support for corners @todo css
          if (split[0] === 'top' || split[0] === 'bottom') {
            switch (split[1]) {
            case 'left':
              offset.left = position.left;
              break;
            case 'right':
              offset.left = position.left + position.width - actualWidth;
            }
          } else if (split[0] === 'left' || split[0] === 'right') {
            switch (split[1]) {
            case 'top':
              offset.top = position.top - actualHeight;
              break;
            case 'bottom':
              offset.top = position.top + position.height;
            }
          }
          return offset;
        }
        return $sbtooltip;
      }
      // Helper functions
      function findElement(query, element) {
        return angular.element((element || document).querySelectorAll(query));
      }
      function fetchTemplate(template) {
        return $q.when($templateCache.get(template) || $http.get(template)).then(function (res) {
          if (angular.isObject(res)) {
            $templateCache.put(template, res.data);
            return res.data;
          }
          return res;
        });
      }
      return TooltipFactory;
    }
  ];
}).directive('bsTooltip', [
  '$window',
  '$location',
  '$sce',
  '$sbtooltip',
  '$$rAF',
  function ($window, $location, $sce, $sbtooltip, $$rAF) {
    return {
      restrict: 'EAC',
      scope: true,
      link: function postLink(scope, element, attr, transclusion) {
        // Directive options
        var options = { scope: scope };
        angular.forEach([
          'template',
          'contentTemplate',
          'placement',
          'container',
          'delay',
          'trigger',
          'keyboard',
          'html',
          'animation',
          'type'
        ], function (key) {
          if (angular.isDefined(attr[key]))
            options[key] = attr[key];
        });
        // Observe scope attributes for change
        angular.forEach(['title'], function (key) {
          attr[key] && attr.$observe(key, function (newValue, oldValue) {
            scope[key] = $sce.trustAsHtml(newValue);
            angular.isDefined(oldValue) && $$rAF(function () {
              sbtooltip && sbtooltip.$applyPlacement();
            });
          });
        });
        // Support scope as an object
        attr.bsTooltip && scope.$watch(attr.bsTooltip, function (newValue, oldValue) {
          if (angular.isObject(newValue)) {
            angular.extend(scope, newValue);
          } else {
            scope.title = newValue;
          }
          angular.isDefined(oldValue) && $$rAF(function () {
            sbtooltip && sbtooltip.$applyPlacement();
          });
        }, true);
        // Initialize popover
        var sbtooltip = $sbtooltip(element, options);
        // Garbage collection
        scope.$on('$destroy', function () {
          sbtooltip.destroy();
          options = null;
          sbtooltip = null;
        });
      }
    };
  }
]);