#ifndef __CONFIG_H_IN__
#define __CONFIG_H_IN__


/*--------------------------------------------------------------------*/
/* API, macros, includes. */

/* Includes. */
#cmakedefine HAVE_SYS_TYPES_H		1

/* API */
#cmakedefine HAVE_MEMSET_S		1
#cmakedefine HAVE_EXPLICIT_BZERO	1
#cmakedefine HAVE_MEMRCHR		1
#cmakedefine HAVE_MEMMEM		1
#cmakedefine HAVE_STRNCASECMP		1
#cmakedefine HAVE_REALLOCARRAY		1
#cmakedefine HAVE_PIPE2			1
#cmakedefine HAVE_ACCEPT4		1

/* Macros. */
#cmakedefine HAVE_SOCK_NONBLOCK		1


/*--------------------------------------------------------------------*/
/* Package information. */
#define PACKAGE			@PROJECT_NAME@
#define VERSION			PACKAGE_VERSION
#define PACKAGE_NAME		"@PACKAGE_NAME@"
#define PACKAGE_VERSION		"@PACKAGE_VERSION@"
#define PACKAGE_URL		"@PACKAGE_URL@"
#define PACKAGE_BUGREPORT	"@PACKAGE_BUGREPORT@"
#define PACKAGE_STRING		"@PACKAGE_STRING@"
#define PACKAGE_DESCRIPTION	"@PACKAGE_DESCRIPTION@"


#endif /* __CONFIG_H_IN__ */
