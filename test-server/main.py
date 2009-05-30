#!/usr/bin/env python

#
# server side test code
# http://ocafe.appspot.com/
#

import wsgiref.handlers
from google.appengine.ext import webapp

class MainHandler(webapp.RequestHandler):

  def get(self):
    if self.request.get('redirecting-with-cookies') != '':
      self.response.headers['Set-Cookie'] = "redirected=redirected"
      self.response.set_status(301)
      self.response.headers['Location'] = 'http://ocafe.appspot.com/?redirect-with-cookies=true'
    elif self.request.get('redirect-with-cookies') != '':
      if self.request.cookies.get('redirected') == 'redirected' :
        self.response.set_status(301)
        self.response.headers['Location'] = 'http://farm4.static.flickr.com/3408/3575435148_80e4a00b19.jpg?v=0'
      else:
        self.response.out.write('cookies not found. redirection failed.')
    elif self.request.get('redirect') != '':
      self.response.set_status(301)
      self.response.headers['Location'] = 'http://farm4.static.flickr.com/3408/3575435148_80e4a00b19.jpg?v=0'
    elif self.request.get('set-cookie') != '':
      self.response.headers['Set-Cookie'] = "redirected=redirected"
      self.response.out.write('cookie redirected=redirected has been set.')
    else:
      self.response.out.write('<html><body>')
      self.response.out.write('post test : <form method="POST" action="/"><input type="text" name="name"/></form>')
      self.response.out.write('</body></html>')

  def post(self):
    self.response.out.write(self.request.body)

def main():
  application = webapp.WSGIApplication([('/', MainHandler)],
                                       debug=True)
  wsgiref.handlers.CGIHandler().run(application)


if __name__ == '__main__':
  main()
