#ifndef __STD_MGMT_H__
#define __STD_MGMT_H__

#include "common.h"

#define COLOR_RED 		31
#define COLOR_GREEN		32
#define COLOR_ORANGE	33
#define COLOR_BLUE		34

void printSystem(const char* str, ...);
void printDebug(const char* str, ...);
void printError(const char* str, ...);
void printSuccess(const char* str, ...);
void setOutputColor(int color);
void unsetOutputColor();
int8_t preg_match(char* str, char* rgx);
size_t preg_split(char* str, char* rgx,char** matches);
#endif
